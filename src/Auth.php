<?php
require_once __DIR__ . '/../config/auth.php';

class Auth {
    public static function init(): void {
        if (session_status() === PHP_SESSION_NONE) {
            // Asegurar nombre de sesión propio
            session_name(AuthConfig::SESSION_NAME);
            session_start();
        }

        // Intentar login por cookie remember si no hay sesión
        if (!self::isLoggedIn() && isset($_COOKIE[AuthConfig::REMEMBER_COOKIE])) {
            $token = (string)$_COOKIE[AuthConfig::REMEMBER_COOKIE];
            if (self::validateRememberToken($token)) {
                // Establecer sesión
                $_SESSION['logged_in'] = true;
                $_SESSION['username'] = AuthConfig::USERNAME;
            } else {
                // Token inválido: limpiar cookie
                self::clearRememberCookie();
            }
        }
    }

    public static function isLoggedIn(): bool {
        return !empty($_SESSION['logged_in']);
    }

    public static function requireLogin(): void {
        self::init();
        if (!self::isLoggedIn()) {
            $loginUrl = rtrim(AuthConfig::BASE_PATH, '/') . '/login.php';
            // Guardar URL de retorno
            $current = self::currentUrl();
            header('Location: ' . $loginUrl . '?r=' . urlencode($current));
            exit;
        }
    }

    public static function attemptLogin(string $username, string $password, bool $remember): bool {
        self::init();
        if ($username !== AuthConfig::USERNAME) {
            return false;
        }
        $ok = false;
        $hash = AuthConfig::PASSWORD_HASH;
        if ($hash !== '') {
            $ok = password_verify($password, $hash);
        } else {
            $ok = hash_equals((string)$password, (string)AuthConfig::PASSWORD_PLAIN);
        }
        if ($ok) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            if ($remember) {
                self::setRememberCookie($username);
            } else {
                self::clearRememberCookie();
            }
        }
        return $ok;
    }

    public static function logout(): void {
        self::init();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        self::clearRememberCookie();
    }

    private static function setRememberCookie(string $username): void {
        $exp = time() + (AuthConfig::REMEMBER_LIFETIME_DAYS * 86400);
        $token = self::buildRememberToken($username, $exp);
        setcookie(
            AuthConfig::REMEMBER_COOKIE,
            $token,
            $exp,
            AuthConfig::BASE_PATH,
            '',
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            true
        );
    }

    private static function clearRememberCookie(): void {
        setcookie(
            AuthConfig::REMEMBER_COOKIE,
            '',
            time() - 3600,
            AuthConfig::BASE_PATH,
            '',
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            true
        );
    }

    private static function buildRememberToken(string $username, int $exp): string {
        $payload = json_encode(['u' => $username, 'e' => $exp], JSON_UNESCAPED_SLASHES);
        $b64 = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
        $sig = hash_hmac('sha256', $b64, AuthConfig::REMEMBER_SECRET);
        return $b64 . '.' . $sig;
    }

    private static function validateRememberToken(string $token): bool {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) return false;
        [$b64, $sig] = $parts;
        $calc = hash_hmac('sha256', $b64, AuthConfig::REMEMBER_SECRET);
        if (!hash_equals($calc, $sig)) return false;
        $json = base64_decode(strtr($b64, '-_', '+/'));
        if ($json === false) return false;
        $data = json_decode($json, true);
        if (!is_array($data) || ($data['u'] ?? null) !== AuthConfig::USERNAME) return false;
        $exp = (int)($data['e'] ?? 0);
        if (time() > $exp) return false;
        return true;
    }

    private static function currentUrl(): string {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return $scheme . '://' . $host . $uri;
    }
}
