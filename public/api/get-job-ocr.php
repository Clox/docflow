<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

function split_ocr_text_into_pages(string $text): array
{
    $rawText = str_replace("\r\n", "\n", $text);
    if ($rawText === '') {
        return [
            ['number' => 1, 'text' => ''],
        ];
    }

    $pages = [];
    if (preg_match_all('/^=== PAGE (\d+) ===\n?/m', $rawText, $matches, PREG_OFFSET_CAPTURE)) {
        $matchCount = count($matches[0]);
        for ($index = 0; $index < $matchCount; $index++) {
            $markerText = (string) ($matches[0][$index][0] ?? '');
            $markerOffset = (int) ($matches[0][$index][1] ?? 0);
            $pageStart = $markerOffset + strlen($markerText);
            $pageEnd = $index + 1 < $matchCount
                ? (int) ($matches[0][$index + 1][1] ?? strlen($rawText))
                : strlen($rawText);
            $pageText = trim(substr($rawText, $pageStart, $pageEnd - $pageStart), "\n");
            $pageNumber = (int) ($matches[1][$index][0] ?? ($index + 1));
            $pages[] = [
                'number' => $pageNumber > 0 ? $pageNumber : ($index + 1),
                'text' => $pageText,
            ];
        }
        return $pages !== [] ? $pages : [['number' => 1, 'text' => trim($rawText, "\n")]];
    }

    if (str_contains($rawText, "\f")) {
        $parts = explode("\f", $rawText);
        foreach ($parts as $index => $pageText) {
            $pages[] = [
                'number' => $index + 1,
                'text' => trim($pageText, "\n"),
            ];
        }
        return $pages !== [] ? $pages : [['number' => 1, 'text' => trim($rawText, "\n")]];
    }

    return [
        ['number' => 1, 'text' => trim($rawText, "\n")],
    ];
}

function derive_page_size_from_words(array $words): array
{
    $maxX = 0.0;
    $maxY = 0.0;

    foreach ($words as $word) {
        if (!is_array($word)) {
            continue;
        }
        $bbox = $word['bbox'] ?? null;
        if (!is_array($bbox) || $bbox === []) {
            continue;
        }

        $isRectBbox = count($bbox) === 4
            && is_numeric($bbox[0] ?? null)
            && is_numeric($bbox[1] ?? null)
            && is_numeric($bbox[2] ?? null)
            && is_numeric($bbox[3] ?? null);

        if ($isRectBbox) {
            $x0 = is_numeric($bbox[0] ?? null) ? (float) $bbox[0] : null;
            $y0 = is_numeric($bbox[1] ?? null) ? (float) $bbox[1] : null;
            $x1 = is_numeric($bbox[2] ?? null) ? (float) $bbox[2] : null;
            $y1 = is_numeric($bbox[3] ?? null) ? (float) $bbox[3] : null;
            if ($x0 !== null && $y0 !== null && $x1 !== null && $y1 !== null) {
                $maxX = max($maxX, $x0, $x1);
                $maxY = max($maxY, $y0, $y1);
            }
            continue;
        }

        foreach ($bbox as $point) {
            if (!is_array($point) || count($point) < 2) {
                continue;
            }
            $x = is_numeric($point[0] ?? null) ? (float) $point[0] : null;
            $y = is_numeric($point[1] ?? null) ? (float) $point[1] : null;
            if ($x !== null && $y !== null) {
                $maxX = max($maxX, $x);
                $maxY = max($maxY, $y);
            }
        }
    }

    return [
        'pageWidth' => $maxX > 0 ? $maxX : null,
        'pageHeight' => $maxY > 0 ? $maxY : null,
    ];
}

function object_page_prefix_for_source(string $source): ?string
{
    if ($source === 'tesseract') {
        return 'tesseract';
    }
    if ($source === 'rapidocr') {
        return 'rapidocr';
    }
    return null;
}

function load_engine_object_pages(string $jobDir, string $engine): array
{
    $jsonPaths = glob($jobDir . '/' . $engine . '_page_*.json') ?: [];
    sort($jsonPaths, SORT_NATURAL);

    $pages = [];
    foreach ($jsonPaths as $index => $jsonPath) {
        $payload = load_json_file($jsonPath);
        if (!is_array($payload)) {
            continue;
        }
        $pageNumber = (int) ($payload['pageNumber'] ?? ($index + 1));
        if ($pageNumber <= 0) {
            $pageNumber = $index + 1;
        }

        $derivedSize = derive_page_size_from_words(is_array($payload['words'] ?? null) ? $payload['words'] : []);
        $pageWidth = is_numeric($payload['pageWidth'] ?? null)
            ? (float) $payload['pageWidth']
            : $derivedSize['pageWidth'];
        $pageHeight = is_numeric($payload['pageHeight'] ?? null)
            ? (float) $payload['pageHeight']
            : $derivedSize['pageHeight'];

        $pages[] = [
            'number' => $pageNumber,
            'text' => is_string($payload['text'] ?? null) ? (string) $payload['text'] : '',
            'pageWidth' => $pageWidth,
            'pageHeight' => $pageHeight,
            'words' => is_array($payload['words'] ?? null) ? $payload['words'] : [],
            'lines' => is_array($payload['lines'] ?? null) ? $payload['lines'] : [],
        ];
    }

    return $pages;
}

try {
    $config = load_config();

    $id = $_GET['id'] ?? '';
    $source = $_GET['source'] ?? 'merged';
    if (!is_string($id) || !is_valid_job_id($id)) {
        http_response_code(404);
        exit;
    }
    if (!is_string($source)) {
        $source = 'merged';
    }

    $normalizedSource = trim($source);
    $filenameBySource = [
        'merged' => 'ocr.txt',
        'tesseract' => 'tesseract.txt',
        'rapidocr' => 'rapidocr.txt',
        'merged-objects' => 'merged_objects.txt',
    ];
    $ocrFilename = $filenameBySource[$normalizedSource] ?? 'ocr.txt';

    $ocrPath = rtrim($config['jobsDirectory'], DIRECTORY_SEPARATOR)
        . DIRECTORY_SEPARATOR . $id
        . DIRECTORY_SEPARATOR . 'ocr.txt';
    $ocrPath = dirname($ocrPath) . DIRECTORY_SEPARATOR . $ocrFilename;

    $jobDir = dirname($ocrPath);

    if ($normalizedSource === 'merged-objects') {
        $mergedObjectPages = ensure_merged_objects_text_from_storage($jobDir, $id);
        if ($mergedObjectPages !== []) {
            $pages = [];
            foreach ($mergedObjectPages as $index => $page) {
                if (!is_array($page)) {
                    continue;
                }
                $pageNumber = is_numeric($page['pageNumber'] ?? null)
                    ? (int) $page['pageNumber']
                    : ($index + 1);
                if ($pageNumber <= 0) {
                    $pageNumber = $index + 1;
                }

                $derivedSize = derive_page_size_from_words(is_array($page['words'] ?? null) ? $page['words'] : []);
                $pageWidth = is_numeric($page['pageWidth'] ?? null)
                    ? (float) $page['pageWidth']
                    : $derivedSize['pageWidth'];
                $pageHeight = is_numeric($page['pageHeight'] ?? null)
                    ? (float) $page['pageHeight']
                    : $derivedSize['pageHeight'];

                $pages[] = [
                    'number' => $pageNumber,
                    'text' => is_string($page['text'] ?? null) ? (string) $page['text'] : '',
                    'pageWidth' => $pageWidth,
                    'pageHeight' => $pageHeight,
                    'words' => is_array($page['words'] ?? null) ? $page['words'] : [],
                    'lines' => [],
                ];
            }

            if ($pages !== []) {
                $chunks = [];
                foreach ($pages as $page) {
                    $pageNumber = (int) ($page['number'] ?? 0);
                    $pageText = is_string($page['text'] ?? null) ? trim((string) $page['text'], "\n") : '';
                    $chunks[] = '=== PAGE ' . $pageNumber . " ===\n" . $pageText;
                }
                json_response([
                    'source' => $normalizedSource,
                    'mode' => 'objects',
                    'text' => implode("\n\n", $chunks),
                    'pages' => $pages,
                ]);
                exit;
            }
        }
    }

    $objectPrefix = object_page_prefix_for_source($normalizedSource);
    if ($objectPrefix !== null) {
        $objectPages = load_engine_object_pages($jobDir, $objectPrefix);
        if ($objectPages !== []) {
            $chunks = [];
            foreach ($objectPages as $page) {
                $pageNumber = (int) ($page['number'] ?? 0);
                $pageText = is_string($page['text'] ?? null) ? trim((string) $page['text'], "\n") : '';
                $chunks[] = '=== PAGE ' . $pageNumber . " ===\n" . $pageText;
            }
            json_response([
                'source' => $normalizedSource,
                'mode' => 'objects',
                'text' => implode("\n\n", $chunks),
                'pages' => $objectPages,
            ]);
            exit;
        }
    }

    if (!is_file($ocrPath)) {
        http_response_code(404);
        exit;
    }

    if ($normalizedSource === 'merged') {
        $mergedObjectPages = ensure_merged_objects_text_from_storage($jobDir, $id);
        if ($mergedObjectPages !== []) {
            $chunks = [];
            $pagePayloads = [];
            foreach ($mergedObjectPages as $index => $page) {
                if (!is_array($page)) {
                    continue;
                }
                $pageNumber = is_numeric($page['pageNumber'] ?? null)
                    ? (int) $page['pageNumber']
                    : ($index + 1);
                if ($pageNumber <= 0) {
                    $pageNumber = $index + 1;
                }
                $pageText = render_grid_text_from_debug_payload($page);
                $normalizedPageText = rtrim($pageText, "\r\n");
                $pagePayloads[] = [
                    'number' => $pageNumber,
                    'text' => $normalizedPageText,
                ];
                $chunks[] = '=== PAGE ' . $pageNumber . " ===\n" . $normalizedPageText;
            }
            if ($chunks !== []) {
                json_response([
                    'source' => $normalizedSource,
                    'mode' => 'text',
                    'text' => implode("\n\n", $chunks),
                    'pages' => $pagePayloads,
                ]);
                exit;
            }
        }

        $ocrObjectsPath = dirname($ocrPath) . DIRECTORY_SEPARATOR . 'ocr-objects.json';
        $ocrObjects = is_file($ocrObjectsPath) ? load_json_file($ocrObjectsPath) : null;
        $pages = is_array($ocrObjects['pages'] ?? null) ? $ocrObjects['pages'] : [];
        if ($pages !== []) {
            $chunks = [];
            $pagePayloads = [];
            foreach ($pages as $index => $page) {
                if (!is_array($page)) {
                    continue;
                }
                $pageText = render_grid_text_from_bbox_objects([$page]);
                $normalizedPageText = rtrim($pageText, "\r\n");
                $pageNumber = $index + 1;
                $pagePayloads[] = [
                    'number' => $pageNumber,
                    'text' => $normalizedPageText,
                ];
                $chunks[] = '=== PAGE ' . $pageNumber . " ===\n" . $normalizedPageText;
            }
            if ($chunks !== []) {
                json_response([
                    'source' => $normalizedSource,
                    'mode' => 'text',
                    'text' => implode("\n\n", $chunks),
                    'pages' => $pagePayloads,
                ]);
                exit;
            }
        }
    }

    $text = file_get_contents($ocrPath);
    if ($text === false) {
        json_response(['text' => ''], 500);
        exit;
    }

    json_response([
        'source' => $normalizedSource,
        'mode' => 'text',
        'text' => $text,
        'pages' => split_ocr_text_into_pages($text),
    ]);
} catch (Throwable $e) {
    json_response(['text' => ''], 500);
}
