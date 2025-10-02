<?php
require_once __DIR__ . '/../../src/Auth.php';
Auth::requireLogin();
require_once __DIR__ . '/../../src/GoogleService.php';

try {
    // Verificar autoload (composer)
    $vendor = __DIR__ . '/../../vendor/autoload.php';
    if (!file_exists($vendor)) {
        http_response_code(500);
        echo 'Falta instalar dependencias. Ejecuta: composer install en la raíz del proyecto.';
        exit;
    }

    $google = new GoogleService();
    $authUrl = $google->getAuthUrl();
    header('Location: ' . $authUrl);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Error iniciando OAuth: ' . htmlspecialchars($e->getMessage());
}
