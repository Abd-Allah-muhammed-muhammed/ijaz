<?php

namespace App\Services\Normalize;

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
}
