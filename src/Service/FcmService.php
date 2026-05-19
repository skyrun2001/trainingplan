<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\DeviceTokenRepository;

/**
 * Sends push notifications via the Firebase Cloud Messaging HTTP v1 API.
 *
 * Authentication uses a service account JSON file (download from Firebase Console →
 * Project Settings → Service Accounts → Generate new private key).
 * Set FIREBASE_CREDENTIALS_PATH in .env.local to the absolute path of that file.
 *
 * Zero external dependencies — uses PHP's built-in openssl + curl.
 */
class FcmService
{
    private ?string $cachedAccessToken = null;
    private int     $tokenExpiresAt    = 0;

    /** Loaded once, then cached for the process lifetime */
    private ?array $credentials = null;

    public function __construct(
        private readonly string               $credentialsPath,
        private readonly DeviceTokenRepository $tokenRepo,
    ) {}

    /**
     * Send a notification to every registered device of $user.
     * Returns the number of tokens successfully delivered to.
     */
    public function notifyUser(User $user, string $title, string $body, array $data = []): int
    {
        $sent = 0;
        foreach ($this->tokenRepo->findForUser($user) as $deviceToken) {
            if ($this->push($deviceToken->getToken(), $title, $body, $data)) {
                $sent++;
            }
        }
        return $sent;
    }

    /**
     * Low-level: send to a single FCM registration token.
     * Returns true on HTTP 200, false otherwise.
     */
    public function push(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        $accessToken = $this->getAccessToken();
        $projectId   = $this->getCredentials()['project_id'];
        $url         = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $payload = json_encode([
            'message' => [
                'token'        => $fcmToken,
                'notification' => ['title' => $title, 'body' => $body],
                'data'         => array_map('strval', $data),
                'android'      => ['priority' => 'high'],
                'apns'         => ['headers' => ['apns-priority' => '10']],
            ],
        ], JSON_THROW_ON_ERROR);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                "Authorization: Bearer {$accessToken}",
            ],
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 10,
        ]);
        curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $status === 200;
    }

    // ── OAuth2 / JWT ──────────────────────────────────────────────────────────

    private function getAccessToken(): string
    {
        if ($this->cachedAccessToken !== null && time() < $this->tokenExpiresAt - 30) {
            return $this->cachedAccessToken;
        }

        $creds = $this->getCredentials();
        $now   = time();

        $header  = $this->b64url((string) json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = $this->b64url((string) json_encode([
            'iss'   => $creds['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ]));

        $unsigned = "{$header}.{$payload}";
        openssl_sign($unsigned, $signature, $creds['private_key'], OPENSSL_ALGO_SHA256);
        $jwt = "{$unsigned}." . $this->b64url($signature);

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]),
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = json_decode((string) curl_exec($ch), true);
        curl_close($ch);

        if (empty($response['access_token'])) {
            throw new \RuntimeException(
                'FCM: failed to obtain access token. Check FIREBASE_CREDENTIALS_PATH and service account permissions.'
            );
        }

        $this->cachedAccessToken = $response['access_token'];
        $this->tokenExpiresAt    = $now + (int) ($response['expires_in'] ?? 3600);

        return $this->cachedAccessToken;
    }

    private function getCredentials(): array
    {
        if ($this->credentials !== null) {
            return $this->credentials;
        }

        if (!file_exists($this->credentialsPath)) {
            throw new \RuntimeException(
                "Firebase credentials file not found at \"{$this->credentialsPath}\". "
                . 'Set FIREBASE_CREDENTIALS_PATH in .env.local to the path of your service account JSON.'
            );
        }

        $json = file_get_contents($this->credentialsPath);
        $data = json_decode((string) $json, true);

        foreach (['project_id', 'client_email', 'private_key'] as $key) {
            if (empty($data[$key])) {
                throw new \RuntimeException("Firebase credentials JSON is missing the \"{$key}\" field.");
            }
        }

        $this->credentials = $data;
        return $this->credentials;
    }

    private function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
