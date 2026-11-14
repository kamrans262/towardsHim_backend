<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SocialTokenVerifier
{
    public function google(string $idToken): array
    {
        $allowed = config('social_auth.google_client_ids', []);
        if (! $allowed) {
            throw new RuntimeException('Google sign-in has not been configured.', 503);
        }

        $response = Http::timeout(10)->get('https://oauth2.googleapis.com/tokeninfo', ['id_token' => $idToken]);
        if (! $response->successful()) {
            throw new RuntimeException('Invalid Google identity token.', 401);
        }
        $claims = $response->json();
        if (! in_array($claims['aud'] ?? null, $allowed, true) ||
            ! in_array($claims['iss'] ?? null, ['accounts.google.com', 'https://accounts.google.com'], true) ||
            ! in_array($claims['email_verified'] ?? false, ['true', true, 1], true) ||
            (int) ($claims['exp'] ?? 0) <= time() ||
            empty($claims['sub'])) {
            throw new RuntimeException('Invalid Google token claims.', 401);
        }
        return [
            'subject' => $claims['sub'],
            'email' => isset($claims['email']) ? strtolower($claims['email']) : null,
            'name' => $claims['name'] ?? null,
        ];
    }

    public function apple(string $idToken, ?string $nonce = null): array
    {
        $allowed = config('social_auth.apple_client_ids', []);
        if (! $allowed) {
            throw new RuntimeException('Apple sign-in has not been configured.', 503);
        }
        $segments = explode('.', $idToken);
        if (count($segments) !== 3) {
            throw new RuntimeException('Invalid Apple identity token.', 401);
        }
        [$head64, $body64, $signature64] = $segments;
        $header = json_decode($this->decode($head64), true);
        $claims = json_decode($this->decode($body64), true);
        if (! is_array($header) || ! is_array($claims) || ($header['alg'] ?? '') !== 'RS256' ||
            ! isset($header['kid'])) {
            throw new RuntimeException('Invalid Apple identity token.', 401);
        }
        $keys = Cache::remember('toward_apple_public_keys', 3600, function (): array {
            $response = Http::timeout(10)->get('https://appleid.apple.com/auth/keys');
            if (! $response->successful()) {
                throw new RuntimeException('Apple token verification unavailable.', 503);
            }
            return $response->json('keys', []);
        });
        $key = collect($keys)->first(fn ($item) => ($item['kid'] ?? '') === $header['kid'] && ($item['kty'] ?? '') === 'RSA');
        if (! $key || ! openssl_verify(
            $head64.'.'.$body64,
            $this->decode($signature64),
            $this->rsaPublicKey($key['n'], $key['e']),
            OPENSSL_ALGO_SHA256
        )) {
            throw new RuntimeException('Invalid Apple token signature.', 401);
        }
        if (($claims['iss'] ?? '') !== 'https://appleid.apple.com' ||
            ! in_array($claims['aud'] ?? null, $allowed, true) ||
            (int) ($claims['exp'] ?? 0) <= time() ||
            (int) ($claims['iat'] ?? 0) > time() + 60 ||
            empty($claims['sub']) ||
            ($nonce !== null && ! hash_equals($nonce, (string) ($claims['nonce'] ?? '')))) {
            throw new RuntimeException('Invalid Apple token claims.', 401);
        }
        return [
            'subject' => $claims['sub'],
            'email' => ! empty($claims['email']) && in_array($claims['email_verified'] ?? '', ['true', true, 1], true)
                ? strtolower($claims['email']) : null,
            'name' => null,
        ];
    }

    private function decode(string $value): string
    {
        $raw = base64_decode(strtr($value, '-_', '+/'), true);
        if ($raw === false) {
            throw new RuntimeException('Invalid token encoding.', 401);
        }
        return $raw;
    }

    private function length(int $length): string
    {
        if ($length < 128) return chr($length);
        $encoded = ltrim(pack('N', $length), "\x00");
        return chr(0x80 | strlen($encoded)).$encoded;
    }

    private function part(string $tag, string $body): string
    {
        return $tag.$this->length(strlen($body)).$body;
    }

    private function rsaPublicKey(string $n, string $e): string
    {
        $modulus = ltrim($this->decode($n), "\x00");
        $exponent = ltrim($this->decode($e), "\x00");
        if ($modulus === '' || $exponent === '') {
            throw new RuntimeException('Invalid Apple signing key.', 401);
        }
        if (ord($modulus[0]) >= 128) $modulus = "\x00".$modulus;
        if (ord($exponent[0]) >= 128) $exponent = "\x00".$exponent;
        $rsa = $this->part("\x30",
            $this->part("\x02", $modulus).$this->part("\x02", $exponent)
        );
        $algorithm = hex2bin('300d06092a864886f70d0101010500');
        $spki = $this->part("\x30", $algorithm.$this->part("\x03", "\x00".$rsa));
        return "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($spki), 64, "\n")."-----END PUBLIC KEY-----\n";
    }
}
