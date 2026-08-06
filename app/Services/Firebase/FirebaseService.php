<?php

namespace App\Services\Firebase;

use App\Services\Firebase\DTO\OutgoingFirebaseMessage;
use App\Services\Firebase\Exceptions\FirebaseAuthenticationException;
use App\Services\Firebase\Exceptions\FirebaseConfigurationException;
use App\Services\Firebase\Exceptions\FirebaseSendException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;

/**
 * Stateless FCM HTTP v1 sender.
 *
 * Safe to reuse across queue-worker notification sends: no per-message instance state.
 * OAuth access tokens are shared via Cache only.
 *
 * Public API: send(OutgoingFirebaseMessage) — replaces the former mutable fluent chain
 * (message/data/target/send) so FirebaseChannel builds a fresh DTO per notification.
 */
class FirebaseService
{
    /**
     * @return array<string, mixed>
     *
     * @throws FirebaseConfigurationException
     * @throws FirebaseAuthenticationException
     * @throws FirebaseSendException
     */
    public function send(OutgoingFirebaseMessage $message): array
    {
        $credentials = $this->credentials();
        $accessToken = $this->accessToken($credentials);

        $url = str_replace(
            '{project_id}',
            $credentials['project_id'],
            (string) config('services.firebase.fcm_send_url'),
        );

        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->asJson()
                ->timeout((int) config('services.firebase.http_timeout', 15))
                ->post($url, [
                    'message' => $message->toFcmMessage(),
                ]);
        } catch (ConnectionException $exception) {
            Log::error('Firebase FCM request failed (connection)', [
                'url' => $url,
                'target_type' => $message->targetType,
                'exception' => $exception->getMessage(),
            ]);

            throw new FirebaseSendException(
                'Firebase FCM request failed: '.$exception->getMessage(),
                context: [
                    'url' => $url,
                    'target_type' => $message->targetType,
                ],
                previous: $exception,
            );
        }

        $body = $response->json() ?? [];

        if ($response->failed() || array_key_exists('error', $body)) {
            $errorMessage = is_array($body['error'] ?? null)
                ? (string) ($body['error']['message'] ?? $response->body())
                : $response->body();

            Log::error('Firebase FCM send rejected', [
                'status' => $response->status(),
                'url' => $url,
                'target_type' => $message->targetType,
                'error' => $body['error'] ?? $response->body(),
            ]);

            throw new FirebaseSendException(
                'Firebase FCM send failed: '.$errorMessage,
                status: $response->status(),
                context: [
                    'url' => $url,
                    'target_type' => $message->targetType,
                    'response' => $body,
                ],
            );
        }

        return $body;
    }

    /**
     * @param  array{project_id: string, client_email: string, private_key: string}  $credentials
     *
     * @throws FirebaseAuthenticationException
     */
    protected function accessToken(array $credentials): string
    {
        $cacheKey = (string) config('services.firebase.cache_key');
        $cached = Cache::get($cacheKey);

        if (is_string($cached) && $cached !== '') {
            return $this->normalizeAccessToken($cached);
        }

        $tokenResponse = $this->requestAccessToken($credentials);
        $accessToken = $tokenResponse['access_token'];
        $expiresIn = (int) ($tokenResponse['expires_in'] ?? 3600);
        $skew = (int) config('services.firebase.token_ttl_skew_seconds', 180);
        $ttl = max(60, $expiresIn - $skew);

        Cache::put($cacheKey, $accessToken, $ttl);

        return $accessToken;
    }

    /**
     * @param  array{project_id: string, client_email: string, private_key: string}  $credentials
     * @return array{access_token: string, expires_in: int, token_type?: string}
     *
     * @throws FirebaseAuthenticationException
     */
    protected function requestAccessToken(array $credentials): array
    {
        $oauthUrl = (string) config('services.firebase.oauth_token_url');

        try {
            $assertion = $this->buildJwtAssertion($credentials, $oauthUrl);

            $response = Http::asForm()
                ->timeout((int) config('services.firebase.http_timeout', 15))
                ->withHeaders(['Cache-Control' => 'no-store'])
                ->post($oauthUrl, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $assertion,
                ]);
        } catch (ConnectionException|JsonException $exception) {
            Log::error('Firebase OAuth token request failed', [
                'url' => $oauthUrl,
                'exception' => $exception->getMessage(),
            ]);

            throw new FirebaseAuthenticationException(
                'Firebase OAuth token request failed: '.$exception->getMessage(),
                context: ['url' => $oauthUrl],
                previous: $exception,
            );
        }

        $body = $response->json() ?? [];

        if ($response->failed() || empty($body['access_token'])) {
            $errorMessage = is_array($body['error'] ?? null)
                ? (string) ($body['error']['message'] ?? json_encode($body['error']))
                : (string) ($body['error_description'] ?? $body['error'] ?? $response->body());

            Log::error('Firebase OAuth token rejected', [
                'status' => $response->status(),
                'url' => $oauthUrl,
                'error' => $body,
            ]);

            throw new FirebaseAuthenticationException(
                'Firebase OAuth token request failed: '.$errorMessage,
                status: $response->status(),
                context: ['url' => $oauthUrl, 'response' => $body],
            );
        }

        return [
            'access_token' => (string) $body['access_token'],
            'expires_in' => (int) ($body['expires_in'] ?? 3600),
            'token_type' => (string) ($body['token_type'] ?? 'Bearer'),
        ];
    }

    /**
     * @param  array{client_email: string, private_key: string}  $credentials
     *
     * @throws FirebaseAuthenticationException
     * @throws JsonException
     */
    protected function buildJwtAssertion(array $credentials, string $audience): string
    {
        $now = time();

        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ], JSON_THROW_ON_ERROR));

        $payload = $this->base64UrlEncode(json_encode([
            'iss' => $credentials['client_email'],
            'aud' => $audience,
            'scope' => 'https://www.googleapis.com/auth/cloud-platform https://www.googleapis.com/auth/firebase.messaging',
            'exp' => $now + 3600,
            'iat' => $now,
        ], JSON_THROW_ON_ERROR));

        $signatureInput = $header.'.'.$payload;

        $signed = openssl_sign(
            $signatureInput,
            $signature,
            $credentials['private_key'],
            OPENSSL_ALGO_SHA256,
        );

        if ($signed !== true) {
            throw new FirebaseAuthenticationException(
                'Failed to sign Firebase OAuth JWT assertion with the configured private key.',
            );
        }

        return $signatureInput.'.'.$this->base64UrlEncode($signature);
    }

    /**
     * @return array{project_id: string, client_email: string, private_key: string}
     *
     * @throws FirebaseConfigurationException
     */
    protected function credentials(): array
    {
        $path = (string) config('services.firebase.credentials');

        if ($path === '' || ! is_readable($path)) {
            $message = "Firebase credentials file is missing or unreadable [{$path}]. Set FIREBASE_AUTH_FILE_PATH to a valid service-account JSON path.";

            if (! app()->environment('local', 'testing')) {
                Log::error($message, ['credentials_path' => $path, 'environment' => app()->environment()]);
            }

            throw new FirebaseConfigurationException($message);
        }

        try {
            /** @var array<string, mixed> $auth */
            $auth = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new FirebaseConfigurationException(
                "Firebase credentials file is not valid JSON [{$path}].",
                previous: $exception,
            );
        }

        foreach (['project_id', 'client_email', 'private_key'] as $required) {
            if (empty($auth[$required]) || ! is_string($auth[$required])) {
                throw new FirebaseConfigurationException(
                    "Firebase credentials file is missing required key [{$required}] at [{$path}].",
                );
            }
        }

        return [
            'project_id' => $auth['project_id'],
            'client_email' => $auth['client_email'],
            'private_key' => $auth['private_key'],
        ];
    }

    protected function normalizeAccessToken(string $token): string
    {
        return str_starts_with($token, 'Bearer ')
            ? substr($token, 7)
            : $token;
    }

    protected function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
