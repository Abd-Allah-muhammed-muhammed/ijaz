<?php

use App\Support\LogRedactor;

it('redacts authorization bearer tokens', function (): void {
    $result = LogRedactor::redact('Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.payload');

    expect($result)->toContain('[REDACTED]')
        ->and($result)->not->toContain('eyJhbGciOiJIUzI1NiJ9.payload');
});

it('redacts cookie headers and session cookie values', function (): void {
    $result = LogRedactor::redact('* **cookie**: XSRF-TOKEN=abc123; ijaz_session=sess456');

    expect($result)->toContain('[REDACTED]')
        ->and($result)->not->toContain('abc123')
        ->and($result)->not->toContain('sess456');
});

it('redacts password and api key fields', function (): void {
    $result = LogRedactor::redact('password=SecretPass "api_key":"sk-live-xyz"');

    expect($result)->not->toContain('SecretPass')
        ->and($result)->not->toContain('sk-live-xyz')
        ->and($result)->toContain('[REDACTED]');
});
