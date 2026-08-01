<?php
declare(strict_types=1);

namespace Docflow\Clients;

final class PersonalIdentityNumberNormalizer
{
    public static function normalize(mixed $value): ?string
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            return null;
        }
        $digits = preg_replace('/\D+/u', '', trim((string) $value));
        if (!is_string($digits) || strlen($digits) !== 12) {
            return null;
        }
        return $digits;
    }
}
