<?php

namespace App\Support;

readonly class Normalize
{
    public static function make(?string $string, string $locale = 'ar'): Normalize
    {
        return new self($string, $locale);
    }

    private ?string $string;

    private function __construct(?string $string, private string $locale = 'ar')
    {
        $this->string = trim($string);
    }

    public function __toString(): string
    {
        if (empty($this->string)) {
            return '';
        }
        $method = $this->locale.'Normalize';
        if (! method_exists($this, $method)) {
            return $this->string;
        }

        return $this->{$this->locale.'Normalize'}();
    }

    private function arNormalize(): array|string|null
    {
        $patterns = ['/[ًٌٍَُِّ~ْ]+/ui', '/\s+/', '/[أإآ]+/ui', '/ة+/ui', '/ى+/ui'];
        $replacements = ['', ' ', 'ا', 'ه', 'ي'];

        return preg_replace($patterns, $replacements, $this->string);
    }

    private function enNormalize(): array|string|null
    {
        return strtolower($this->string);
    }

    private function urNormalize(): array|string|null
    {
        return strtolower($this->string);
    }

    private function hiNormalize(): array|string|null
    {
        return strtolower($this->string);
    }

    public function toString(): string
    {
        return $this->__toString();
    }

    /**
     * Convert Arabic-Indic (U+0660–U+0669) and Persian (U+06F0–U+06F9) digits to Western 0–9.
     *
     * Mobile keyboards in Arabic locales often emit these digits; PHP's strtotime / Laravel's
     * `date` rule only accept ASCII numerals.
     */
    public static function westernDigits(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return strtr($value, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
    }
}
