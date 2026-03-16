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
    ];
    $ocrFilename = $filenameBySource[$normalizedSource] ?? 'ocr.txt';

    $ocrPath = rtrim($config['jobsDirectory'], DIRECTORY_SEPARATOR)
        . DIRECTORY_SEPARATOR . $id
        . DIRECTORY_SEPARATOR . 'ocr.txt';
    $ocrPath = dirname($ocrPath) . DIRECTORY_SEPARATOR . $ocrFilename;

    if (!is_file($ocrPath)) {
        http_response_code(404);
        exit;
    }

    if ($normalizedSource === 'merged') {
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
        'text' => $text,
        'pages' => split_ocr_text_into_pages($text),
    ]);
} catch (Throwable $e) {
    json_response(['text' => ''], 500);
}
