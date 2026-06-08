<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $config = load_config();

    $id = $_GET['id'] ?? '';
    if (!is_string($id) || !is_valid_job_id($id)) {
        http_response_code(404);
        exit;
    }

    $jobDir = rtrim($config['jobsDirectory'], DIRECTORY_SEPARATOR)
        . DIRECTORY_SEPARATOR . $id;
    $extractedPath = $jobDir
        . DIRECTORY_SEPARATOR . 'extracted.json';

    if (!is_file($extractedPath)) {
        http_response_code(404);
        exit;
    }

    $extracted = load_json_file($extractedPath);
    if (!is_array($extracted)) {
        json_response(['labels' => [], 'fields' => [], 'clients' => []]);
        exit;
    }

    $labels = $extracted['labelMatches'] ?? [];
    if (!is_array($labels)) {
        $labels = [];
    }

    $systemLabels = $extracted['systemLabelMatches'] ?? [];
    if (!is_array($systemLabels)) {
        $systemLabels = [];
    }
    $labels = array_values(array_merge($systemLabels, $labels));
    $fieldValues = $extracted['extractionFields'] ?? [];
    if (!is_array($fieldValues)) {
        $fieldValues = [];
    }
    $fieldMeta = $extracted['extractionFieldMeta'] ?? [];
    if (!is_array($fieldMeta)) {
        $fieldMeta = [];
    }
    $clientMatches = $extracted['clientMatches'] ?? [];
    if (!is_array($clientMatches)) {
        $clientMatches = [];
    }
    $senderCandidates = [];
    $zoneMatches = $extracted['zoneMatches'] ?? [];
    if (!is_array($zoneMatches)) {
        $zoneMatches = [];
    }
    try {
        $job = load_json_file($jobDir . DIRECTORY_SEPARATOR . 'job.json');
        if (is_array($job)) {
            $autoResult = is_array($extracted['autoArchivingResult'] ?? null) ? $extracted['autoArchivingResult'] : [];
            $matchedSenderId = isset($autoResult['senderId']) && (int) ($autoResult['senderId'] ?? 0) > 0
                ? (int) $autoResult['senderId']
                : null;
            $selectedSenderId = isset($job['selectedSenderId']) && (int) ($job['selectedSenderId'] ?? 0) > 0
                ? (int) $job['selectedSenderId']
                : null;
            $senderSummary = build_job_sender_summary($extracted, $jobDir, $matchedSenderId, $selectedSenderId);
            if (is_array($senderSummary) && is_array($senderSummary['senders'] ?? null)) {
                $senderCandidates = array_values(array_filter(
                    $senderSummary['senders'],
                    static fn($row): bool => is_array($row)
                ));
            }
            $rules = load_active_archiving_rules();
            $matchingPayload = load_matching_settings_payload();
            $ocrText = load_job_analysis_text($jobDir, null);
            $lineGeometries = build_matching_line_geometries_for_job($job, $ocrText);
            $replacementMap = replacement_map(
                is_array($matchingPayload['replacements'] ?? null) ? $matchingPayload['replacements'] : []
            );
            $positionSettings = matching_position_settings_from_payload($matchingPayload);
            $liveZoneMatches = detect_configured_zone_matches(
                split_lines_for_matching($ocrText),
                is_array($rules['zones'] ?? null) ? $rules['zones'] : [],
                $replacementMap,
                $lineGeometries,
                is_array($rules['valuePatterns'] ?? null) ? $rules['valuePatterns'] : []
            );
            $zoneMatches = $liveZoneMatches;
            $matchingLines = split_lines_for_matching($ocrText);

            foreach (is_array($rules['systemFields'] ?? null) ? $rules['systemFields'] : [] as $systemField) {
                if (!is_array($systemField)) {
                    continue;
                }
                $extractor = is_string($systemField['extractor'] ?? null) ? trim((string) $systemField['extractor']) : '';
                $systemFieldKey = is_string($systemField['systemFieldKey'] ?? null) ? trim((string) $systemField['systemFieldKey']) : '';
                $key = is_string($systemField['key'] ?? null) ? trim((string) $systemField['key']) : '';
                if ($extractor === 'primary_date' || $systemFieldKey === 'primary_date' || $key === 'primary_date') {
                    $primaryDateResult = extract_primary_date_field_result(
                        $matchingLines,
                        $lineGeometries,
                        is_array($systemField['primaryDateHeuristics'] ?? null) ? $systemField['primaryDateHeuristics'] : [],
                        $replacementMap,
                        $positionSettings
                    );
                    $primaryDateMatches = primary_date_result_matches($primaryDateResult, $lineGeometries);
                    if (is_string($primaryDateResult['value'] ?? null) && (string) $primaryDateResult['value'] !== '') {
                        $fieldValues['primary_date'] = [(string) $primaryDateResult['value']];
                    }
                    $fieldMeta['primary_date'] = [
                        'key' => 'primary_date',
                        'name' => is_string($systemField['name'] ?? null) && trim((string) $systemField['name']) !== ''
                            ? trim((string) $systemField['name'])
                            : 'Huvuddatum',
                        'extractor' => 'primary_date',
                        'value' => is_string($primaryDateResult['value'] ?? null) ? (string) $primaryDateResult['value'] : null,
                        'raw' => is_string($primaryDateResult['raw'] ?? null) ? (string) $primaryDateResult['raw'] : null,
                        'confidence' => isset($primaryDateResult['confidence']) && is_numeric($primaryDateResult['confidence']) ? (float) $primaryDateResult['confidence'] : 0.0,
                        'baseConfidence' => isset($primaryDateResult['confidence']) && is_numeric($primaryDateResult['confidence']) ? (float) $primaryDateResult['confidence'] : 0.0,
                        'finalConfidence' => isset($primaryDateResult['confidence']) && is_numeric($primaryDateResult['confidence']) ? (float) $primaryDateResult['confidence'] : 0.0,
                        'score' => is_numeric($primaryDateResult['selectedCandidate']['score'] ?? null) ? (float) $primaryDateResult['selectedCandidate']['score'] : null,
                        'fullConfidenceScore' => is_numeric($primaryDateResult['fullConfidenceScore'] ?? null) ? (float) $primaryDateResult['fullConfidenceScore'] : null,
                        'matches' => $primaryDateMatches,
                    ];
                    continue;
                }

                if ($extractor !== 'title' && $systemFieldKey !== 'title' && $key !== 'title') {
                    continue;
                }

                $titleResult = extract_title_field_result(
                    $matchingLines,
                    $lineGeometries,
                    is_array($systemField['titleHeuristics'] ?? null) ? $systemField['titleHeuristics'] : [],
                    $positionSettings
                );
                $titleMatches = title_result_matches($titleResult, $lineGeometries);
                if (is_string($titleResult['value'] ?? null) && (string) $titleResult['value'] !== '') {
                    $fieldValues['title'] = [(string) $titleResult['value']];
                }
                $fieldMeta['title'] = [
                    'key' => 'title',
                    'name' => is_string($systemField['name'] ?? null) && trim((string) $systemField['name']) !== ''
                        ? trim((string) $systemField['name'])
                        : 'Rubrik',
                    'extractor' => 'title',
                    'value' => is_string($titleResult['value'] ?? null) ? (string) $titleResult['value'] : null,
                    'raw' => is_string($titleResult['raw'] ?? null) ? (string) $titleResult['raw'] : null,
                    'confidence' => isset($titleResult['confidence']) && is_numeric($titleResult['confidence']) ? (float) $titleResult['confidence'] : 0.0,
                    'baseConfidence' => isset($titleResult['confidence']) && is_numeric($titleResult['confidence']) ? (float) $titleResult['confidence'] : 0.0,
                    'finalConfidence' => isset($titleResult['confidence']) && is_numeric($titleResult['confidence']) ? (float) $titleResult['confidence'] : 0.0,
                    'score' => is_numeric($titleResult['selectedCandidate']['score'] ?? null) ? (float) $titleResult['selectedCandidate']['score'] : null,
                    'fullConfidenceScore' => is_numeric($titleResult['fullConfidenceScore'] ?? null) ? (float) $titleResult['fullConfidenceScore'] : null,
                    'matches' => $titleMatches,
                ];
            }
        }
    } catch (Throwable $e) {
        // Keep the stored match payload usable even if live zone overlay calculation fails.
    }

    $isInvalidPositionMatch = static function (array $match): bool {
        return is_string($match['positionPenaltyAxis'] ?? null)
            && in_array(trim((string) $match['positionPenaltyAxis']), ['invalid', 'invalid_bbox'], true);
    };
    $normalizePatternConfidence = static function (?string $matchType, ?string $matchSource, $confidence, $baseConfidence = null, $finalConfidence = null): array {
        $resolvedConfidence = isset($confidence) ? (float) $confidence : null;
        $resolvedBaseConfidence = is_numeric($baseConfidence) ? (float) $baseConfidence : $resolvedConfidence;
        $resolvedFinalConfidence = is_numeric($finalConfidence) ? (float) $finalConfidence : $resolvedBaseConfidence;

        if ($matchType === 'pattern' && $matchSource === 'pattern') {
            $resolvedConfidence = 1.0;
            $resolvedBaseConfidence = 1.0;
            $resolvedFinalConfidence = 1.0;
        }

        return [
            'confidence' => $resolvedConfidence,
            'baseConfidence' => $resolvedBaseConfidence,
            'finalConfidence' => $resolvedFinalConfidence,
        ];
    };
    $isRejectedByZoneBarrier = static function (array $match) use (&$zoneMatches): bool {
        if ($zoneMatches === []) {
            return false;
        }
        $labelBbox = normalize_debug_word_bbox($match['labelBbox'] ?? null);
        $valueBbox = normalize_debug_word_bbox($match['valueBbox'] ?? null);
        if ($labelBbox === null || $valueBbox === null) {
            return false;
        }
        $pageNumber = is_int($match['pageNumber'] ?? null) ? (int) $match['pageNumber'] : null;
        return candidate_crosses_zone_barrier(
            $labelBbox,
            $valueBbox,
            connector_points_between_bboxes($labelBbox, $valueBbox),
            $zoneMatches,
            $pageNumber
        );
    };
    $normalizeCaptureRanges = static function ($ranges): array {
        if (!is_array($ranges)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static function ($range): ?array {
                if (!is_array($range)) {
                    return null;
                }
                $start = is_int($range['start'] ?? null) ? (int) $range['start'] : null;
                $end = is_int($range['end'] ?? null) ? (int) $range['end'] : null;
                if ($start === null || $end === null || $start < 0 || $end <= $start) {
                    return null;
                }
                return [
                    'start' => $start,
                    'end' => $end,
                ];
            },
            $ranges
        ), static fn ($range): bool => is_array($range)));
    };
    $inferCaptureRangesFromExtractedRaw = static function (array $match) use ($normalizeCaptureRanges): array {
        $storedRanges = $normalizeCaptureRanges($match['captureRanges'] ?? null);
        if ($storedRanges !== []) {
            return $storedRanges;
        }

        $matchText = is_string($match['matchText'] ?? null) ? (string) $match['matchText'] : '';
        $extractedRaw = is_string($match['raw'] ?? null) ? (string) $match['raw'] : '';
        if ($matchText === '' || $extractedRaw === '' || $matchText === $extractedRaw) {
            return [];
        }

        $start = strpos($matchText, $extractedRaw);
        if ($start === false) {
            return [];
        }

        $end = $start + strlen($extractedRaw);
        if ($end <= $start) {
            return [];
        }

        $charStart = utf8_strlen_safe(substr($matchText, 0, $start));
        $charLength = utf8_strlen_safe(substr($matchText, $start, $end - $start));
        if ($charLength <= 0) {
            return [];
        }

        return [[
            'start' => $charStart,
            'end' => $charStart + $charLength,
        ]];
    };

    $fields = [];
    $allFieldKeys = array_values(array_unique(array_merge(array_keys($fieldValues), array_keys($fieldMeta))));
    foreach ($allFieldKeys as $fieldKey) {
        if (!is_string($fieldKey) || trim($fieldKey) === '') {
            continue;
        }

        $resolvedKey = trim($fieldKey);
        $legacyField = is_array($fieldValues[$resolvedKey] ?? null) ? $fieldValues[$resolvedKey] : [];
        $meta = is_array($fieldMeta[$resolvedKey] ?? null) ? $fieldMeta[$resolvedKey] : [];
        $matches = is_array($meta['matches'] ?? null) ? $meta['matches'] : [];
        $firstMatch = is_array($matches[0] ?? null) ? $matches[0] : [];
        $values = normalize_auto_archiving_field_value_list($fieldValues[$resolvedKey] ?? null);

        $candidateRows = [];
        if ($matches !== []) {
            foreach ($matches as $match) {
                if (!is_array($match)) {
                    continue;
                }
                if ($isInvalidPositionMatch($match)) {
                    continue;
                }
                if ($isRejectedByZoneBarrier($match)) {
                    continue;
                }

                $candidateValue = normalize_auto_archiving_field_value_item($match['value'] ?? null);
                if ($candidateValue === null) {
                    continue;
                }

                $matchType = is_string($match['matchType'] ?? null) ? trim((string) $match['matchType']) : '';
                if ($matchType === '') {
                    $source = is_string($match['source'] ?? null) ? trim((string) $match['source']) : '';
                    if ($source === 'pattern') {
                        $matchType = 'pattern';
                    }
                }
                $matchSource = is_string($match['source'] ?? null) ? trim((string) $match['source']) : '';
                $labelText = is_string($match['labelText'] ?? null) ? trim((string) $match['labelText']) : '';
                $searchTerm = is_string($match['searchTerm'] ?? null) ? trim((string) $match['searchTerm']) : '';
                $hasKey = $labelText !== '' || $searchTerm !== '';
                $normalizedConfidence = $normalizePatternConfidence(
                    $matchType !== '' ? $matchType : null,
                    $matchSource !== '' ? $matchSource : null,
                    $match['confidence'] ?? null,
                    $match['baseConfidence'] ?? null,
                    $match['finalConfidence'] ?? null
                );
                $confidence = $normalizedConfidence['confidence'];
                $score = is_numeric($match['score'] ?? null) ? (float) $match['score'] : null;
                $rawMatchText = is_string($match['matchText'] ?? null)
                    ? (string) $match['matchText']
                    : (is_string($match['raw'] ?? null) ? (string) $match['raw'] : '');

                $candidateRows[] = [
                    'value' => $candidateValue,
                    'source' => is_string($match['source'] ?? null) ? (string) $match['source'] : '',
                    'labelText' => $labelText,
                    'between' => is_string($match['between'] ?? null) ? (string) $match['between'] : '',
                    'searchTerm' => $searchTerm,
                    'hasKey' => $hasKey,
                    'scopeType' => is_string($match['scopeType'] ?? null) ? trim((string) $match['scopeType']) : '',
                    'scopeText' => is_string($match['scopeText'] ?? null) ? trim((string) $match['scopeText']) : '',
                    'scopeIsRegex' => ($match['scopeIsRegex'] ?? false) === true,
                    'scopeMatchedText' => is_string($match['scopeMatchedText'] ?? null) ? trim((string) $match['scopeMatchedText']) : '',
                    'scopeLineIndex' => is_int($match['scopeLineIndex'] ?? null) ? (int) $match['scopeLineIndex'] : null,
                    'extractedRaw' => is_string($match['raw'] ?? null) ? (string) $match['raw'] : '',
                    'raw' => $rawMatchText,
                    'matchText' => $rawMatchText,
                    'captureRanges' => $inferCaptureRangesFromExtractedRaw($match),
                    'confidence' => $confidence,
                    'baseConfidence' => $normalizedConfidence['baseConfidence'],
                    'finalConfidence' => $normalizedConfidence['finalConfidence'],
                    'noisePenalty' => is_numeric($match['noisePenalty'] ?? null) ? (float) $match['noisePenalty'] : null,
                    'trailingDelimiterPenalty' => is_numeric($match['trailingDelimiterPenalty'] ?? null) ? (float) $match['trailingDelimiterPenalty'] : null,
                    'otherMatchKeyPenalty' => is_numeric($match['otherMatchKeyPenalty'] ?? null) ? (float) $match['otherMatchKeyPenalty'] : null,
                    'positionPenalty' => is_numeric($match['positionPenalty'] ?? null) ? (float) $match['positionPenalty'] : (is_numeric($match['directionPenalty'] ?? null) ? (float) $match['directionPenalty'] : null),
                    'verticalDistancePenalty' => is_numeric($match['verticalDistancePenalty'] ?? null) ? (float) $match['verticalDistancePenalty'] : null,
                    'verticalDistance' => is_numeric($match['verticalDistance'] ?? null) ? (float) $match['verticalDistance'] : null,
                    'verticalNormalizedDistance' => is_numeric($match['verticalNormalizedDistance'] ?? null) ? (float) $match['verticalNormalizedDistance'] : null,
                    'positionPenaltyAxis' => is_string($match['positionPenaltyAxis'] ?? null) ? trim((string) $match['positionPenaltyAxis']) : '',
                    'mainDirection' => is_string($match['mainDirection'] ?? null) ? trim((string) $match['mainDirection']) : '',
                    'invalidReason' => is_string($match['invalidReason'] ?? null) ? trim((string) $match['invalidReason']) : '',
                    'labelBbox' => $hasKey && is_array($match['labelBbox'] ?? null) ? $match['labelBbox'] : null,
                    'valueBbox' => is_array($match['valueBbox'] ?? null) ? $match['valueBbox'] : null,
                    'valueBBoxIndexes' => is_array($match['valueBBoxIndexes'] ?? null) ? array_values(array_filter(
                        $match['valueBBoxIndexes'],
                        static fn($index): bool => is_int($index) && $index > 0
                    )) : [],
                    'pageNumber' => is_int($match['pageNumber'] ?? null) ? (int) $match['pageNumber'] : null,
                    'noiseText' => is_string($match['noiseText'] ?? null) ? (string) $match['noiseText'] : '',
                    'noiseSegments' => array_values(array_filter(array_map(
                        static function ($segment): ?array {
                            if (!is_array($segment)) {
                                return null;
                            }
                            $text = is_string($segment['text'] ?? null) ? (string) $segment['text'] : '';
                            $lineIndex = is_int($segment['lineIndex'] ?? null) ? (int) $segment['lineIndex'] : null;
                            $start = is_int($segment['start'] ?? null) ? (int) $segment['start'] : null;
                            $end = is_int($segment['end'] ?? null) ? (int) $segment['end'] : null;
                            if ($text === '' || $lineIndex === null || $start === null || $end === null || $end <= $start) {
                                return null;
                            }
                            return [
                                'text' => $text,
                                'lineIndex' => $lineIndex,
                                'start' => $start,
                                'end' => $end,
                            ];
                        },
                        is_array($match['noiseSegments'] ?? null) ? $match['noiseSegments'] : []
                    ), static fn ($segment): bool => is_array($segment))),
                    'score' => $score,
                    'fullConfidenceScore' => is_numeric($match['fullConfidenceScore'] ?? null) ? (float) $match['fullConfidenceScore'] : null,
                    'yRatio' => is_numeric($match['yRatio'] ?? null) ? (float) $match['yRatio'] : null,
                    'signals' => is_array($match['signals'] ?? null) ? array_values(array_filter(
                        $match['signals'],
                        static fn($signal): bool => is_array($signal)
                    )) : [],
                    'lineIndex' => is_int($match['lineIndex'] ?? null) ? (int) $match['lineIndex'] : PHP_INT_MAX,
                    'labelLineIndex' => is_int($match['labelLineIndex'] ?? null) ? (int) $match['labelLineIndex'] : null,
                    'start' => is_int($match['start'] ?? null) ? (int) $match['start'] : PHP_INT_MAX,
                    'ruleSetIndex' => is_int($match['ruleSetIndex'] ?? null) ? (int) $match['ruleSetIndex'] : null,
                    'matchType' => $matchType !== '' ? $matchType : null,
                ];
            }
        }

        if ($values === [] && $candidateRows === []) {
            continue;
        }

        if ($candidateRows === []) {
            if ($matches !== []) {
                continue;
            }

            foreach ($values as $index => $candidateValue) {
                $fallbackMatchType = $index === 0 && is_string($firstMatch['matchType'] ?? null)
                    ? trim((string) $firstMatch['matchType'])
                    : '';
                if ($fallbackMatchType === '') {
                    $fallbackSource = $index === 0 && is_string($firstMatch['source'] ?? null)
                        ? trim((string) $firstMatch['source'])
                        : '';
                    if ($fallbackSource === 'pattern') {
                        $fallbackMatchType = 'pattern';
                    }
                }
                $fallbackSource = $index === 0 && is_string($firstMatch['source'] ?? null)
                    ? trim((string) $firstMatch['source'])
                    : ($index === 0 && is_string($legacyField['source'] ?? null) ? trim((string) $legacyField['source']) : '');
                $fallbackLabelText = $index === 0 && is_string($firstMatch['labelText'] ?? null)
                    ? trim((string) $firstMatch['labelText'])
                    : '';
                $fallbackSearchTerm = $index === 0 && is_string($firstMatch['searchTerm'] ?? null)
                    ? trim((string) $firstMatch['searchTerm'])
                    : '';
                $fallbackHasKey = $fallbackLabelText !== '' || $fallbackSearchTerm !== '';
                $fallbackNormalizedConfidence = $normalizePatternConfidence(
                    $fallbackMatchType !== '' ? $fallbackMatchType : null,
                    $fallbackSource !== '' ? $fallbackSource : null,
                    $index === 0
                        ? (isset($firstMatch['confidence']) ? (float) $firstMatch['confidence'] : (isset($legacyField['confidence']) ? (float) $legacyField['confidence'] : null))
                        : null,
                    $index === 0 && is_numeric($firstMatch['baseConfidence'] ?? null) ? (float) $firstMatch['baseConfidence'] : null,
                    $index === 0 && is_numeric($firstMatch['finalConfidence'] ?? null) ? (float) $firstMatch['finalConfidence'] : null
                );
                $fallbackConfidence = $fallbackNormalizedConfidence['confidence'];
                $fallbackScore = $index === 0 && is_numeric($firstMatch['score'] ?? null)
                    ? (float) $firstMatch['score']
                    : null;
                $fallbackRawMatchText = $index === 0 && is_string($firstMatch['matchText'] ?? null)
                    ? (string) $firstMatch['matchText']
                    : ($index === 0 && is_string($firstMatch['raw'] ?? null)
                        ? (string) $firstMatch['raw']
                        : ($index === 0 && is_string($legacyField['matchText'] ?? null)
                            ? (string) $legacyField['matchText']
                            : ($index === 0 && is_string($legacyField['raw'] ?? null) ? (string) $legacyField['raw'] : '')));

                $candidateRows[] = [
                    'value' => $candidateValue,
                    'source' => $index === 0 && is_string($firstMatch['source'] ?? null)
                        ? (string) $firstMatch['source']
                        : ($index === 0 && is_string($legacyField['source'] ?? null) ? (string) $legacyField['source'] : ''),
                    'labelText' => $fallbackLabelText,
                    'between' => $index === 0 && is_string($firstMatch['between'] ?? null)
                        ? (string) $firstMatch['between']
                        : '',
                    'searchTerm' => $fallbackSearchTerm,
                    'hasKey' => $fallbackHasKey,
                    'scopeType' => $index === 0 && is_string($firstMatch['scopeType'] ?? null)
                        ? trim((string) $firstMatch['scopeType'])
                        : '',
                    'scopeText' => $index === 0 && is_string($firstMatch['scopeText'] ?? null)
                        ? trim((string) $firstMatch['scopeText'])
                        : '',
                    'scopeIsRegex' => $index === 0 && (($firstMatch['scopeIsRegex'] ?? false) === true),
                    'scopeMatchedText' => $index === 0 && is_string($firstMatch['scopeMatchedText'] ?? null)
                        ? trim((string) $firstMatch['scopeMatchedText'])
                        : '',
                    'scopeLineIndex' => $index === 0 && is_int($firstMatch['scopeLineIndex'] ?? null)
                        ? (int) $firstMatch['scopeLineIndex']
                        : null,
                    'extractedRaw' => $index === 0 && is_string($firstMatch['raw'] ?? null)
                        ? (string) $firstMatch['raw']
                        : ($index === 0 && is_string($legacyField['raw'] ?? null) ? (string) $legacyField['raw'] : ''),
                    'raw' => $fallbackRawMatchText,
                    'matchText' => $fallbackRawMatchText,
                    'captureRanges' => $index === 0 ? $inferCaptureRangesFromExtractedRaw($firstMatch) : [],
                    'confidence' => $fallbackConfidence,
                    'baseConfidence' => $fallbackNormalizedConfidence['baseConfidence'],
                    'finalConfidence' => $fallbackNormalizedConfidence['finalConfidence'],
                    'noisePenalty' => $index === 0 && is_numeric($firstMatch['noisePenalty'] ?? null) ? (float) $firstMatch['noisePenalty'] : null,
                    'trailingDelimiterPenalty' => $index === 0 && is_numeric($firstMatch['trailingDelimiterPenalty'] ?? null) ? (float) $firstMatch['trailingDelimiterPenalty'] : null,
                    'otherMatchKeyPenalty' => $index === 0 && is_numeric($firstMatch['otherMatchKeyPenalty'] ?? null) ? (float) $firstMatch['otherMatchKeyPenalty'] : null,
                    'positionPenalty' => $index === 0 && is_numeric($firstMatch['positionPenalty'] ?? null) ? (float) $firstMatch['positionPenalty'] : ($index === 0 && is_numeric($firstMatch['directionPenalty'] ?? null) ? (float) $firstMatch['directionPenalty'] : null),
                    'verticalDistancePenalty' => $index === 0 && is_numeric($firstMatch['verticalDistancePenalty'] ?? null) ? (float) $firstMatch['verticalDistancePenalty'] : null,
                    'verticalDistance' => $index === 0 && is_numeric($firstMatch['verticalDistance'] ?? null) ? (float) $firstMatch['verticalDistance'] : null,
                    'verticalNormalizedDistance' => $index === 0 && is_numeric($firstMatch['verticalNormalizedDistance'] ?? null) ? (float) $firstMatch['verticalNormalizedDistance'] : null,
                    'positionPenaltyAxis' => $index === 0 && is_string($firstMatch['positionPenaltyAxis'] ?? null) ? trim((string) $firstMatch['positionPenaltyAxis']) : '',
                    'mainDirection' => $index === 0 && is_string($firstMatch['mainDirection'] ?? null) ? trim((string) $firstMatch['mainDirection']) : '',
                    'invalidReason' => $index === 0 && is_string($firstMatch['invalidReason'] ?? null) ? trim((string) $firstMatch['invalidReason']) : '',
                    'labelBbox' => $fallbackHasKey && $index === 0 && is_array($firstMatch['labelBbox'] ?? null) ? $firstMatch['labelBbox'] : null,
                    'valueBbox' => $index === 0 && is_array($firstMatch['valueBbox'] ?? null) ? $firstMatch['valueBbox'] : null,
                    'pageNumber' => $index === 0 && is_int($firstMatch['pageNumber'] ?? null) ? (int) $firstMatch['pageNumber'] : null,
                    'noiseText' => $index === 0 && is_string($firstMatch['noiseText'] ?? null) ? (string) $firstMatch['noiseText'] : '',
                    'noiseSegments' => $index === 0
                        ? array_values(array_filter(array_map(
                            static function ($segment): ?array {
                                if (!is_array($segment)) {
                                    return null;
                                }
                                $text = is_string($segment['text'] ?? null) ? (string) $segment['text'] : '';
                                $lineIndex = is_int($segment['lineIndex'] ?? null) ? (int) $segment['lineIndex'] : null;
                                $start = is_int($segment['start'] ?? null) ? (int) $segment['start'] : null;
                                $end = is_int($segment['end'] ?? null) ? (int) $segment['end'] : null;
                                if ($text === '' || $lineIndex === null || $start === null || $end === null || $end <= $start) {
                                    return null;
                                }
                                return [
                                    'text' => $text,
                                    'lineIndex' => $lineIndex,
                                    'start' => $start,
                                    'end' => $end,
                                ];
                            },
                            is_array($firstMatch['noiseSegments'] ?? null) ? $firstMatch['noiseSegments'] : []
                        ), static fn ($segment): bool => is_array($segment)))
                        : [],
                    'score' => $fallbackScore,
                    'fullConfidenceScore' => $index === 0 && is_numeric($firstMatch['fullConfidenceScore'] ?? null) ? (float) $firstMatch['fullConfidenceScore'] : null,
                    'yRatio' => $index === 0 && is_numeric($firstMatch['yRatio'] ?? null) ? (float) $firstMatch['yRatio'] : null,
                    'signals' => $index === 0 && is_array($firstMatch['signals'] ?? null) ? array_values(array_filter(
                        $firstMatch['signals'],
                        static fn($signal): bool => is_array($signal)
                    )) : [],
                    'lineIndex' => $index === 0 && is_int($firstMatch['lineIndex'] ?? null) ? (int) $firstMatch['lineIndex'] : PHP_INT_MAX,
                    'labelLineIndex' => $index === 0 && is_int($firstMatch['labelLineIndex'] ?? null) ? (int) $firstMatch['labelLineIndex'] : null,
                    'start' => $index === 0 && is_int($firstMatch['start'] ?? null) ? (int) $firstMatch['start'] : PHP_INT_MAX,
                    'matchType' => $fallbackMatchType !== '' ? $fallbackMatchType : null,
                ];
            }
        }

        $compareCandidateRows = static function (array $left, array $right): int {
            $leftConfidence = isset($left['confidence']) && is_numeric($left['confidence']) ? (float) $left['confidence'] : -1.0;
            $rightConfidence = isset($right['confidence']) && is_numeric($right['confidence']) ? (float) $right['confidence'] : -1.0;
            $confidenceCompare = $rightConfidence <=> $leftConfidence;
            if ($confidenceCompare !== 0) {
                return $confidenceCompare;
            }

            $lineCompare = ((int) ($left['lineIndex'] ?? PHP_INT_MAX)) <=> ((int) ($right['lineIndex'] ?? PHP_INT_MAX));
            if ($lineCompare !== 0) {
                return $lineCompare;
            }

            return ((int) ($left['start'] ?? PHP_INT_MAX)) <=> ((int) ($right['start'] ?? PHP_INT_MAX));
        };

        usort($candidateRows, $compareCandidateRows);

        $fields[$resolvedKey] = [
            'key' => is_string($meta['key'] ?? null) && trim((string) $meta['key']) !== ''
                ? trim((string) $meta['key'])
                : (is_string($legacyField['key'] ?? null) && trim((string) $legacyField['key']) !== '' ? trim((string) $legacyField['key']) : $resolvedKey),
            'name' => is_string($meta['name'] ?? null) && trim((string) $meta['name']) !== ''
                ? trim((string) $meta['name'])
                : (is_string($legacyField['name'] ?? null) && trim((string) $legacyField['name']) !== '' ? trim((string) $legacyField['name']) : $resolvedKey),
            'value' => $values[0] ?? null,
            'values' => $values,
            'matches' => $candidateRows,
            'source' => is_string($firstMatch['source'] ?? null)
                ? (string) $firstMatch['source']
                : (is_string($legacyField['source'] ?? null) ? (string) $legacyField['source'] : ''),
            'labelText' => is_string($firstMatch['labelText'] ?? null)
                ? trim((string) $firstMatch['labelText'])
                : '',
            'between' => is_string($firstMatch['between'] ?? null)
                ? (string) $firstMatch['between']
                : '',
            'extractedRaw' => is_string($firstMatch['raw'] ?? null)
                ? (string) $firstMatch['raw']
                : (is_string($legacyField['raw'] ?? null) ? (string) $legacyField['raw'] : ''),
            'raw' => is_string($firstMatch['matchText'] ?? null)
                ? (string) $firstMatch['matchText']
                : (is_string($firstMatch['raw'] ?? null)
                    ? (string) $firstMatch['raw']
                    : (is_string($legacyField['matchText'] ?? null)
                        ? (string) $legacyField['matchText']
                        : (is_string($legacyField['raw'] ?? null) ? (string) $legacyField['raw'] : ''))),
            'confidence' => $normalizePatternConfidence(
                is_string($firstMatch['matchType'] ?? null) ? trim((string) $firstMatch['matchType']) : null,
                is_string($firstMatch['source'] ?? null) ? trim((string) $firstMatch['source']) : null,
                isset($firstMatch['confidence'])
                    ? (float) $firstMatch['confidence']
                    : (isset($legacyField['confidence']) ? (float) $legacyField['confidence'] : null),
                is_numeric($firstMatch['baseConfidence'] ?? null) ? (float) $firstMatch['baseConfidence'] : null,
                is_numeric($firstMatch['finalConfidence'] ?? null) ? (float) $firstMatch['finalConfidence'] : null
            )['confidence'],
        ];
    }

    $clients = [];
    foreach ($clientMatches as $match) {
        if (!is_array($match)) {
            continue;
        }

        $dirName = is_string($match['dirName'] ?? null) ? trim((string) $match['dirName']) : '';
        $displayName = is_string($match['displayName'] ?? null) ? trim((string) $match['displayName']) : $dirName;
        $signals = [];
        foreach (is_array($match['matchedSignals'] ?? null) ? $match['matchedSignals'] : [] as $signal) {
            if (!is_array($signal)) {
                continue;
            }

            $label = is_string($signal['label'] ?? null) ? trim((string) $signal['label']) : '';
            $value = is_string($signal['value'] ?? null) ? trim((string) $signal['value']) : '';
            $type = is_string($signal['type'] ?? null) ? trim((string) $signal['type']) : '';
            if ($label === '' || $value === '') {
                continue;
            }

            $signals[] = [
                'type' => $type,
                'label' => $label,
                'value' => $value,
            ];
        }

        if ($displayName === '' || $signals === []) {
            continue;
        }

        $clients[] = [
            'dirName' => $dirName,
            'displayName' => $displayName,
            'signals' => $signals,
        ];
    }

    json_response([
        'labels' => $labels,
        'senders' => $senderCandidates,
        'fields' => $fields,
        'clients' => $clients,
        'zones' => $zoneMatches,
    ]);
} catch (Throwable $e) {
    json_response([
        'labels' => [],
        'senders' => [],
        'fields' => [],
        'clients' => [],
        'zones' => [],
    ], 500);
}
