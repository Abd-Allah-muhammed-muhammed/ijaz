<?php

namespace App\Services\Sms;

use Illuminate\Contracts\Support\Arrayable;
use Stringable;

class Phone implements Arrayable, Stringable
{
    private array $country;

    private bool $plus = false;

    private function __construct(private readonly string $number, ?string $countryCode = null)
    {
        if (! is_null($countryCode)) {
            $this->for($countryCode);
        }
    }

    public function for(string $countryCode): Phone
    {
        $this->country = config("phones.$countryCode", []);

        return $this;
    }

    public static function make(string $number, ?string $countryCode = 'KSA'): Phone
    {
        return new self($number, $countryCode);
    }

    public static function placeholder(string $county_code): string
    {
        $pattern = self::conf($county_code, 'regex');
        $key = self::conf($county_code, 'key');
        $pattern = (string) str_replace(['?', '^', '\\', '$', '/', '{', '}', '<key>', '<provider>', '<digits>', 'd'], '', $pattern);
        $parts = array_map(fn ($x) => trim($x, ' \n\r\t\v\0()'), array_slice(explode(')(', $pattern), 1));
        [$provider, $digits] = $parts;
        $digits = str_repeat('x', $digits);

        return "$key$provider$digits";
    }

    public static function conf(string $locale, ?string $key = null, mixed $default = null): mixed
    {
        if (empty($key)) {
            return config('phones.'.$locale);
        }

        return config("phones.{$locale}.{$key}", $default);
    }

    public function withPlus(): self
    {
        $this->plus = true;

        return $this;
    }

    public function withoutPlus(): self
    {
        $this->plus = false;

        return $this;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function toString(): string
    {
        if ($this->isNotValid()) {
            return '';
        }
        $segments = $this->segments();
        $key = $this->plus ? '+' : '';
        $key .= $this->country['key'];

        return "{$key}{$segments['provider']}{$segments['digits']}";
    }

    public function isNotValid(): bool
    {
        return ! $this->isValid();
    }

    public function isValid(): bool
    {
        $regex = $this->config('regex');
        if (! $regex) {
            return false;
        }

        return preg_match($regex, $this->number);
    }

    public function config(?string $key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->country;
        }

        return $this->country[$key] ?? $default;
    }

    public function segments(): array
    {
        preg_match($this->config('regex'), $this->number, $matches);

        return $matches;
    }

    public function number(): string
    {
        return $this->number;
    }

    public function toArray(): array
    {
        return $this->all();
    }

    public function all(): array
    {
        if ($this->isNotValid()) {
            return [];
        }
        $segments = $this->segments();
        $base = $segments['provider'].$segments['digits'];

        return array_map(function ($key) use ($base) {
            return $key.$base;
        }, $this->config('all_keys'));
    }

    public function withoutKeys(): string
    {
        $segments = $this->segments();

        return "{$segments['provider']}{$segments['digits']}";
    }
}
