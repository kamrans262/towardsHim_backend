<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FirebasePushService
{
    private ?string $accessToken = null;

    /**
     * Returns sent, invalid or retry. Push never runs without explicit
     * provider configuration. Never log device tokens or private keys.
     */
    public function send(string $deviceToken, string $title, string $body, array $data): string
    {
        if (! config('services.firebase.push_enabled', false)) return 'retry';

        $project = (string) config('services.firebase.project_id');
        if ($project === '') return 'retry';

        $accessToken = $this->token();
        if (! $accessToken) return 'retry';

        $response = Http::withToken($accessToken)->timeout(15)->post(
            'https://fcm.googleapis.com/v1/projects/'.rawurlencode($project).'/messages:send',
            [
                'message' => [
                    'token' => $deviceToken,
                    'notification' => ['title' => $title, 'body' => $body],
                    'data' => $data,
                ],
            ]
        );

        if ($response->successful()) return 'sent';

        $code = (string) $response->json('error.status');
        if (in_array($code, ['UNREGISTERED', 'INVALID_ARGUMENT'], true)) {
            // INVALID_ARGUMENT is only treated as invalid when the request
            // itself was valid and the provider specifically rejects token.
            if ($code === 'UNREGISTERED') return 'invalid';
        }

        Log::warning('Toward HIM FCM send failed', ['status' => $response->status(), 'provider_code' => $code]);
        if ($response->serverError() || $response->status() === 429) {
            throw new RuntimeException('FCM temporarily unavailable.');
        }

        return 'retry';
    }

    private function token(): ?string
    {
        if ($this->accessToken) return $this->accessToken;

        $path = config('services.firebase.service_account_path');
        if (! is_string($path) || $path === '' || ! is_readable($path)) return null;

        $account = json_decode((string) file_get_contents($path), true);
        if (! is_array($account) || empty($account['private_key']) || empty($account['client_email'])) {
            return null;
        }

        $now = time();
        $header = $this->base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = $this->base64url(json_encode([
            'iss' => $account['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));
        $signedData = $header.'.'.$claims;
        if (! openssl_sign($signedData, $signature, $account['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('FCM signing failed.');
        }
        $jwt = $signedData.'.'.$this->base64url($signature);
        $response = Http::asForm()->timeout(15)->post(
            'https://oauth2.googleapis.com/token',
            [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ],
        );

        if (! $response->successful()) {
            Log::warning('Toward HIM FCM OAuth failed', ['status' => $response->status()]);
            throw new RuntimeException('FCM OAuth token unavailable.');
        }

        $this->accessToken = $response->json('access_token');
        return $this->accessToken;
    }

    private function base64url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
