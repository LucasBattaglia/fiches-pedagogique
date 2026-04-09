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
            // Utilise APP_URL ou reconstruit l'URL de base
            $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
            if ($base === '.' || $base === '\\') $base = '';
            header('Location: ' . $base . '/auth/login?redirect=' . urlencode($redirect));
            exit;
        }
    }

    // ---- Google OAuth ----

    public static function getGoogleAuthUrl(array $config): string
    {
        // redirect_uri : priorité à la config, sinon reconstruction depuis APP_URL
        $redirectUri = $config['redirect_uri'] ?? self::buildRedirectUri();

        $params = http_build_query([
            'client_id'     => $config['client_id'],
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => self::generateState(),
            'access_type'   => 'online',
        ]);
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
    }

    public static function handleGoogleCallback(array $config, string $code): ?array
    {
        // Vérification CSRF state
        $state = $_GET['state'] ?? '';
        if (empty($_SESSION['oauth_state']) || $state !== $_SESSION['oauth_state']) {
            error_log('Google OAuth: state mismatch');
            return null;
        }
        unset($_SESSION['oauth_state']);

        $redirectUri = $config['redirect_uri'] ?? self::buildRedirectUri();

        // Échange code -> token
        $tokenResp = self::httpPost('https://oauth2.googleapis.com/token', [
            'code'          => $code,
            'client_id'     => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri'  => $redirectUri,
            'grant_type'    => 'authorization_code',
        ]);

        if (!isset($tokenResp['access_token'])) {
            error_log('Google OAuth token error: ' . json_encode($tokenResp));
            return null;
        }

        // Récupérer infos user
        $userInfo = self::httpGet('https://www.googleapis.com/oauth2/v3/userinfo', $tokenResp['access_token']);
        if (!isset($userInfo['sub'])) {
            error_log('Google OAuth userinfo error: ' . json_encode($userInfo));
            return null;
        }

        return UserDAO::getInstance()->findOrCreateByOAuth(
            'google',
            $userInfo['sub'],
            $userInfo['email'],
            $userInfo['family_name'] ?? '',
            $userInfo['given_name']  ?? '',
            $userInfo['picture']     ?? null
        );
    }

    // ── Construit le redirect_uri depuis APP_URL ou la requête courante ──
    private static function buildRedirectUri(): string
    {
        // Priorité 1 : APP_URL dans .env
        $appUrl = getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? '');
        if ($appUrl) {
            return rtrim($appUrl, '/') . '/auth/google/callback';
        }

        // Priorité 2 : reconstruction depuis $_SERVER
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base   = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
        if ($base === '.' || $base === '\\') $base = '';

        return $scheme . '://' . $host . $base . '/auth/google/callback';
    }

    private static function generateState(): string
    {
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;
        return $state;
    }

    // ── cURL avec gestion SSL (cacert.pem si présent) ──────────────
    private static function getCacertPath(): string
    {
        return getenv('CACERT_PATH') ?: ($_ENV['CACERT_PATH'] ?? '');
    }

    private static function applySSL(\CurlHandle $ch): void
    {
        $cacert = self::getCacertPath();
        if ($cacert && file_exists($cacert)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_CAINFO, $cacert);
        } else {
            // En production avec un certificat valide, SSL_VERIFYPEER devrait être true
            // En dev local sans cacert configuré, on désactive temporairement
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }
    }

    private static function httpPost(string $url, array $data): array
    {
        $ch = curl_init($url);
        self::applySSL($ch);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 15,
        ]);
        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) {
            error_log('AuthService cURL POST error: ' . $err);
            return [];
        }
        return json_decode($resp, true) ?? [];
    }

    private static function httpGet(string $url, string $token): array
    {
        $ch = curl_init($url);
        self::applySSL($ch);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ["Authorization: Bearer $token"],
            CURLOPT_TIMEOUT        => 15,
        ]);
        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) {
            error_log('AuthService cURL GET error: ' . $err);
            return [];
        }
        return json_decode($resp, true) ?? [];
    }
}