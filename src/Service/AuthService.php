<?php
// src/Service/AuthService.php

namespace src\Service;

use src\DAO\UserDAO;

class AuthService
{
    public static function login(array $user): void
    {
        $_SESSION['user'] = [
            'id'     => $user['id'],
            'nom'    => $user['nom'],
            'prenom' => $user['prenom'],
            'email'  => $user['email'],
            'avatar' => $user['avatar_url'] ?? null,
            'admin'  => (bool)($user['is_admin'] ?? false),
        ];
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
        session_destroy();
    }

    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user']['id']);
    }

    public static function currentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function currentUserId(): ?int
    {
        return isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
    }

    public static function requireLogin(string $redirect = '/'): void
    {
        if (!self::isLoggedIn()) {
            header('Location: /auth/login?redirect=' . urlencode($redirect));
            exit;
        }
    }

    // ---- Google OAuth ----

    public static function getGoogleAuthUrl(array $config): string
    {
        $params = http_build_query([
            'client_id'     => $config['client_id'],
            'redirect_uri'  => $config['redirect_uri'],
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => self::generateState(),
        ]);
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
    }

    public static function handleGoogleCallback(array $config, string $code): ?array
    {
        // Échange code -> token
        $tokenResp = self::httpPost('https://oauth2.googleapis.com/token', [
            'code'          => $code,
            'client_id'     => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri'  => $config['redirect_uri'],
            'grant_type'    => 'authorization_code',
        ]);
        if (!isset($tokenResp['access_token'])) return null;

        // Récupérer infos user
        $userInfo = self::httpGet('https://www.googleapis.com/oauth2/v3/userinfo', $tokenResp['access_token']);
        if (!isset($userInfo['sub'])) return null;

        return UserDAO::getInstance()->findOrCreateByOAuth(
            'google',
            $userInfo['sub'],
            $userInfo['email'],
            $userInfo['family_name'] ?? '',
            $userInfo['given_name'] ?? '',
            $userInfo['picture'] ?? null
        );
    }

    private static function generateState(): string
    {
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;
        return $state;
    }

    private static function httpPost(string $url, array $data): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        return json_decode($resp, true) ?? [];
    }

    private static function httpGet(string $url, string $token): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ["Authorization: Bearer $token"],
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        return json_decode($resp, true) ?? [];
    }
}
