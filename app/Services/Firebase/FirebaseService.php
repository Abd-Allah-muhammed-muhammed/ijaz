<?php

namespace App\Services\Firebase;

use Exception;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

class FirebaseService
{
    private array $auth = [];

    private string $access_token = '';

    private array $message = [
        'notification' => ['title' => '', 'body' => ''],
        'token' => '',
        'topic' => '',
    ];

    /**
     * @throws JsonException
     */
    public function __construct()
    {
        $this->setAuth(config('firebase.auth_file_path'));
    }

    /**
     * @throws JsonException
     */
    public function setAuth(string $auth): static
    {
        $this->auth = json_decode(file_get_contents($auth), true, 512, JSON_THROW_ON_ERROR);

        return $this;
    }

    public function message(string $title, string|array $body): static
    {
        $this->message['notification'] = [
            'title' => $title, 'body' => $body,
        ];

        return $this;
    }

    public function data(array $data): static
    {
        if (empty($data)) {
            return $this;
        }
        $this->message['data'] = (object) $data;

        return $this;
    }

    public function target(string $type, string $value): static
    {
        if (! in_array($type, ['topic', 'token'])) {
            throw new InvalidArgumentException("Invalid Argument type  '$type' not supported ");
        }

        if (empty(trim($value))) {
            throw new InvalidArgumentException('Empty value provided for target message not supported');
        }

        if ($type === 'topic') {
            $this->toTopic($value);
        } else {
            $this->to($value);
        }

        return $this;
    }

    public function toTopic(string $topic): static
    {
        $this->message['topic'] = $topic;
        unset($this->message['token']);

        return $this;
    }

    public function to(string $token): static
    {
        $this->message['token'] = $token;
        unset($this->message['topic']);

        return $this;
    }

    /**
     * @throws Exception
     */
    public function notify()
    {
        unset($this->message['notification']);
        //    var body = json.encode({
        //      "message": {
        //    "token": token,
        //        "data": {"title": title, "body": bodyText, "type": type},
        //        "android": {
        //      "priority": "high" // Ensure high priority for Android
        //        },
        //        "apns": {
        //      "headers": {
        //        "apns-priority": "10", // Immediate delivery
        //          },
        //          "payload": {
        //        "aps": {
        //          //change it when version 48
        //          /"alert": {
        //            // This ensures the notification is displayed on iOS
        //            /"title": title,
        //                "body": bodyText/
        //              },/
        //              "sound": "default", // Sound settings for iOS
        //              "content-available": 1 // Triggers background handling on iOS
        //            }
        //          }
        //        }
        //      }
        //    });
        $this->message['apns'] = [
            'headers' => [
                'apns-priority' => '10',
            ],
            'payload' => [
                'aps' => [
                    'alert' => [
                        'title' => $this->message['data']->title ?? '',
                        'body' => $this->message['data']->body ?? '',
                    ],
                    'sound' => 'default',
                    'content-available' => 1,
                ],
            ],
            //      'fcm_options' => [
            //        'analytics_label' => 'analytics',
            //      ],
        ];
        $this->message['android'] = [
            'priority' => 'high',
            //      'notification' => [
            //        'color' => '#0A0A0A',
            //        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            //        'channelId' => 'high_importance_channel'
            //      ],
            //      'fcm_options' => [
            //        'analytics_label' => 'analytics',
            //      ],
        ];

        return $this->send();
    }

    /**
     * @throws Exception
     */
    public function send()
    {
        $this->authenticate();

        return $this->post("https://fcm.googleapis.com/v1/projects/{$this->auth['project_id']}/messages:send", [
            'headers' => [
                "Authorization: {$this->access_token}",
                'Content-Type: application/json',
            ],
            'body' => json_encode(['message' => $this->message], JSON_THROW_ON_ERROR),
        ]);

    }

    /**
     * @throws JsonException
     */
    protected function authenticate(): static
    {
        $this->access_token = $this->setToken();

        return $this;
    }

    /**
     * @throws JsonException
     */
    protected function setToken(): string
    {
        $token = $this->getToken();
        if ($token) {
            return $token;
        }
        $authToken = $this->requestNewToken();

        $token = "{$authToken['token_type']} {$authToken['access_token']}";

        // sub 3m to insure token always valid
        Cache::put(config('firebase.cache_key'), $token, $authToken['expires_in'] - (60 / 3));

        return $token;
    }

    protected function getToken()
    {
        return Cache::get(config('firebase.cache_key'));

    }

    /**
     * @throws JsonException
     */
    protected function requestNewToken()
    {
        $header = json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ], JSON_THROW_ON_ERROR);
        $now = time();
        $payload = json_encode([
            'iss' => $this->auth['client_email'],
            'aud' => 'https://oauth2.googleapis.com/token',
            'scope' => 'https://www.googleapis.com/auth/cloud-platform https://www.googleapis.com/auth/firebase.messaging',
            'exp' => $now + 3600,
            'iat' => $now,
        ], JSON_THROW_ON_ERROR);

        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        $signatureInput = $base64Header.'.'.$base64Payload;
        openssl_sign($signatureInput, $signature, $this->auth['private_key'], OPENSSL_ALGO_SHA256);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $this->post('https://oauth2.googleapis.com/token', [
            'headers' => [
                'Cache-Control: no-store',
                'Content-Type: application/x-www-form-urlencoded',
            ],
            'body' => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $signatureInput.'.'.$base64Signature,
            ]),
        ]);
    }

    /**
     * @throws RuntimeException|JsonException
     */
    public function post(string $url, array $options = [])
    {
        $options = array_merge([
            'headers' => [],
            'body' => '',
        ], $options);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $options['headers']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $options['body']);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            throw new RuntimeException($error);
        }
        $response = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(json_last_error_msg());
        }
        if (array_key_exists('error', $response)) {
            throw new RuntimeException($response['error']['message']);
        }

        return $response;
    }
}
