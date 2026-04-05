<?php
namespace src\Service;

class GoogleAuthService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private string $cacertPath;

    public function __construct()
    {
        $cfg = $this->loadEnv();
        $this->clientId     = $cfg['GOOGLE_CLIENT_ID']     ?? '';
        $this->clientSecret = $cfg['GOOGLE_CLIENT_SECRET'] ?? '';
        $this->redirectUri  = ($cfg['APP_URL'] ?? 'http://localhost') . '/auth/google/token-callback';
        $this->cacertPath   = $cfg['CACERT_PATH'] ?? '';
        if (empty($this->clientId) || empty($this->clientSecret)) {
            throw new \RuntimeException('GOOGLE_CLIENT_ID et GOOGLE_CLIENT_SECRET requis dans .env');
        }
    }

    private function loadEnv(): array
    {
        $cfg = [];
        $envPath = __DIR__ . '/../../.env';
        if (!file_exists($envPath)) return $cfg;
        foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            [$key, $val] = array_pad(explode('=', $line, 2), 2, '');
            $cfg[trim($key)] = trim($val);
        }
        return $cfg;
    }

    private function applySSL(\CurlHandle $ch): void
    {
        if (!empty($this->cacertPath) && file_exists($this->cacertPath)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_CAINFO, $this->cacertPath);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
    }

    public function redirect(): void
    {
        $params = http_build_query([
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'response_type' => 'code',
            'scope'         => 'https://www.googleapis.com/auth/drive https://www.googleapis.com/auth/documents',
            'access_type'   => 'offline',
            'prompt'        => 'consent',
        ]);
        header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
        exit;
    }

    public function callback(): void
    {
        if (empty($_GET['code'])) {
            http_response_code(400);
            echo "Erreur : pas de code reçu de Google.";
            return;
        }
        $ch = curl_init('https://oauth2.googleapis.com/token');
        $this->applySSL($ch);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'code'          => $_GET['code'],
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => $this->redirectUri,
            'grant_type'    => 'authorization_code',
        ]));
        $resp = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (empty($resp['refresh_token'])) {
            echo "<h2>Erreur</h2><pre>" . htmlspecialchars(json_encode($resp, JSON_PRETTY_PRINT)) . "</pre>";
            echo "<p>Révoquer l'app sur <a href='https://myaccount.google.com/permissions'>myaccount.google.com/permissions</a> puis relancer /auth/google/init</p>";
            return;
        }
        $refreshToken = $resp['refresh_token'];
        $envPath = __DIR__ . '/../../.env';
        if (file_exists($envPath)) {
            $content = file_get_contents($envPath);
            if (str_contains($content, 'GOOGLE_REFRESH_TOKEN=')) {
                $content = preg_replace('/^GOOGLE_REFRESH_TOKEN=.*$/m', 'GOOGLE_REFRESH_TOKEN=' . $refreshToken, $content);
            } else {
                $content .= "\nGOOGLE_REFRESH_TOKEN=" . $refreshToken . "\n";
            }
            file_put_contents($envPath, $content);
        }
        echo "<!DOCTYPE html><html><head><meta charset='utf-8'></head><body style='font-family:monospace;padding:30px'>";
        echo "<h2 style='color:green'>✓ Authentification Google réussie !</h2>";
        echo "<p style='color:green'>✓ GOOGLE_REFRESH_TOKEN enregistré dans .env</p>";
        echo "<p>Le PdfService peut maintenant générer des PDFs.</p>";
        echo "</body></html>";
    }
}