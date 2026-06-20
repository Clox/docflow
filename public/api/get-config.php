<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $config = load_config();
    $jbig2 = jbig2_status_payload();
    $python = python_status_payload();
    $rapidocr = rapidocr_status_payload();
    $spylls = spylls_status_payload();
    json_response([
        'inboxDirectory' => $config['inboxDirectory'],
        'jobsDirectory' => $config['jobsDirectory'],
        'outputBaseDirectory' => $config['outputBaseDirectory'],
        'ocrDebugExportDirectory' => $config['ocrDebugExportDirectory'] ?? DEFAULT_OCR_DEBUG_EXPORT_DIRECTORY,
        'ocrSkipExistingText' => (bool) $config['ocrSkipExistingText'],
        'ocrOptimizeLevel' => (int) $config['ocrOptimizeLevel'],
        'stateUpdateTransport' => (string) $config['stateUpdateTransport'],
        'ocrTextExtractionMethod' => (string) $config['ocrTextExtractionMethod'],
        'chromeExtensionId' => docflow_chrome_extension_id(),
        'chromeExtensionVersion' => docflow_chrome_extension_version(),
        'chromeExtensionDirectory' => docflow_chrome_extension_directory(),
        'chromeExtensionSuppressMissingNotice' => (bool) ($config['chromeExtensionSuppressMissingNotice'] ?? false),
        'ocrPdfTextSubstitutions' => is_array($config['ocrPdfTextSubstitutions'] ?? null)
            ? $config['ocrPdfTextSubstitutions']
            : [],
        'multiLineTextBlocks' => normalize_multiline_text_block_settings(
            $config['multiLineTextBlocks'] ?? []
        ),
        'layoutAnalysis' => normalize_layout_analysis_settings(
            $config['layoutAnalysis'] ?? []
        ),
        'jbig2' => $jbig2,
        'python' => $python,
        'rapidocr' => $rapidocr,
        'spylls' => $spylls,
    ]);
} catch (Throwable $e) {
    json_response([
        'error' => $e->getMessage(),
    ], 500);
}
