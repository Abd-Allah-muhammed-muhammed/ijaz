<?php

namespace Modules\Payment\Services;

use RuntimeException;

class RajhiEncryptionService
{
    private string $key;

    private string $iv;

    private string $cipher = 'AES-256-CBC';

    public function __construct()
    {
        $mode = config('payment.drivers.rajhi.mode', 'test');
        $config = config("payment.drivers.rajhi.{$mode}", []);

        $this->key = $config['resource_key'] ?? '';
        $this->iv = $config['encryption_iv'] ?? 'PGKEYENCDECIVSPC';

        if (empty($this->key)) {
            throw new RuntimeException('Rajhi resource_key is not configured.');
        }
    }

    /**
     * Encrypt an array payload to trandata string.
     * Input:  plain array (e.g. id, password, action, amt, ...)
     * Output: AES-256-CBC encrypted, base64-encoded string
     */
    public function encrypt(array $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);

        $encrypted = openssl_encrypt(
            $json,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $this->iv,
        );

        if ($encrypted === false) {
            throw new RuntimeException('Rajhi encryption failed: '.openssl_error_string());
        }

        return base64_encode($encrypted);
    }

    /**
     * Decrypt a trandata string from Neoleap callback.
     * Input:  AES-256-CBC encrypted, base64-encoded string
     * Output: decoded array
     */
    public function decrypt(string $trandata): array
    {
        $decoded = base64_decode($trandata, strict: true);

        if ($decoded === false) {
            throw new RuntimeException('Rajhi decryption failed: invalid base64 trandata.');
        }

        $decrypted = openssl_decrypt(
            $decoded,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $this->iv,
        );

        if ($decrypted === false) {
            throw new RuntimeException('Rajhi decryption failed: '.openssl_error_string());
        }

        $result = json_decode($decrypted, associative: true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Rajhi decryption failed: invalid JSON — '.json_last_error_msg());
        }

        return $result;
    }
}
