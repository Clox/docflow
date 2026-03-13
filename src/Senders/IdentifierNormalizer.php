<?php
declare(strict_types=1);

namespace Docflow\Senders;

final class IdentifierNormalizer
{
    public static function normalizeOrgNumber(string $value): ?string
    {
        $digits = self::digitsOnly($value);
        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 12 && (str_starts_with($digits, '19') || str_starts_with($digits, '20'))) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) !== 10) {
            return null;
        }

        return $digits;
    }

    public static function normalizeBankgiro(string $value): ?string
    {
        $digits = self::digitsOnly($value);
        if ($digits === '') {
            return null;
        }

        $length = strlen($digits);
        if ($length < 5 || $length > 12) {
            return null;
        }

        return $digits;
    }

    public static function normalizePlusgiro(string $value): ?string
    {
        $digits = self::digitsOnly($value);
        if ($digits === '') {
            return null;
        }

        $length = strlen($digits);
        if ($length < 5 || $length > 12) {
            return null;
        }

        return $digits;
    }

    public static function normalizeName(string $value): ?string
    {
        $lower = function_exists('mb_strtolower')
            ? mb_strtolower($value)
            : strtolower($value);
        $trimmed = trim($lower);
        if ($trimmed === '') {
            return null;
        }

        $collapsed = preg_replace('/\s+/u', ' ', $trimmed);
        if (!is_string($collapsed)) {
            return $trimmed;
        }

        $result = trim($collapsed);
        return $result !== '' ? $result : null;
    }

    public static function normalizeByType(string $type, string $value): ?string
    {
        if ($type === 'org_number') {
            return self::normalizeOrgNumber($value);
        }
        if ($type === 'bankgiro') {
            return self::normalizeBankgiro($value);
        }
        if ($type === 'plusgiro') {
            return self::normalizePlusgiro($value);
        }
        if ($type === 'name') {
            return self::normalizeName($value);
        }

        return null;
    }

    private static function digitsOnly(string $value): string
    {
        $normalized = preg_replace('/[^0-9]/', '', $value);
        if (!is_string($normalized)) {
            return '';
        }

        return $normalized;
    }
}
