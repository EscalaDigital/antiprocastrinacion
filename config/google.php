<?php

class GoogleConfig {
    public static function getClientConfig(): array {
        $path = __DIR__ . '/google_client.json';
        if (file_exists($path)) {
            $json = file_get_contents($path);
            $data = json_decode($json, true);
            if (isset($data['web'])) {
                return $data['web'];
            }
        }
        // Fallback a variables de entorno si no hay archivo
        $clientId = getenv('GOOGLE_CLIENT_ID') ?: '';
        $clientSecret = getenv('GOOGLE_CLIENT_SECRET') ?: '';
        $authUri = 'https://accounts.google.com/o/oauth2/auth';
        $tokenUri = 'https://oauth2.googleapis.com/token';
        return [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'auth_uri' => $authUri,
            'token_uri' => $tokenUri
        ];
    }

    public static function getRedirectUri(): string {
        $base = self::guessBaseUrl();
        return rtrim($base, '/') . '/api/google/callback.php';
    }

    private static function guessBaseUrl(): string {
        // Asumimos XAMPP en localhost bajo carpeta antiprocastrinacion
        // Cambiar si se despliega en otra ruta
        return 'http://localhost/antiprocastrinacion';
    }
}
