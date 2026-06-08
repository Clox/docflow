<?php
declare(strict_types=1);

namespace Docflow\Senders;

final class NameNormalizer
{
    public static function normalize(string $value): string
    {
        $lowered = function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower(strtr($value, [
                'À' => 'à',
                'Á' => 'á',
                'Â' => 'â',
                'Ã' => 'ã',
                'Ä' => 'ä',
                'Å' => 'å',
                'Æ' => 'æ',
                'Ç' => 'ç',
                'È' => 'è',
                'É' => 'é',
                'Ê' => 'ê',
                'Ë' => 'ë',
                'Ì' => 'ì',
                'Í' => 'í',
                'Î' => 'î',
                'Ï' => 'ï',
                'Ð' => 'ð',
                'Ñ' => 'ñ',
                'Ò' => 'ò',
                'Ó' => 'ó',
                'Ô' => 'ô',
                'Õ' => 'õ',
                'Ö' => 'ö',
                'Ø' => 'ø',
                'Ù' => 'ù',
                'Ú' => 'ú',
                'Û' => 'û',
                'Ü' => 'ü',
                'Ý' => 'ý',
                'Þ' => 'þ',
                'Š' => 'š',
                'Ž' => 'ž',
            ]));
        $hyphens = preg_replace('/[\x{2010}-\x{2015}\x{2212}]+/u', '-', $lowered);
        $source = is_string($hyphens) ? $hyphens : $lowered;
        $compactHyphens = preg_replace('/\s*-\s*/u', '-', $source);
        $source = is_string($compactHyphens) ? $compactHyphens : $source;
        $collapsed = preg_replace('/\s+/u', ' ', $source);

        return trim(is_string($collapsed) ? $collapsed : $source);
    }
}
