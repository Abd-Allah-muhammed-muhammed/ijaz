<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Public Firebase web client config for the messaging service worker.
 * Values are the same public VITE_FIREBASE_* keys baked into the frontend bundle
 * (VAPID is the Web Push *public* key — safe to expose; never the server private key).
 */
class FirebaseWebConfigController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $web = config('services.firebase.web', []);

        return response()->json([
            'apiKey' => $web['api_key'] ?? '',
            'authDomain' => $web['auth_domain'] ?? '',
            'projectId' => $web['project_id'] ?? '',
            'messagingSenderId' => $web['messaging_sender_id'] ?? '',
            'appId' => $web['app_id'] ?? '',
        ]);
    }
}
