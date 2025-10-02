<?php
require_once __DIR__ . '/../../src/Auth.php';
Auth::requireLogin();
require_once __DIR__ . '/../../src/GoogleService.php';

try {
    if (!isset($_GET['code'])) {
        throw new Exception('Código de autorización no presente');
    }
    $google = new GoogleService();
    $google->handleCallback($_GET['code']);
    // Redirigir a la app principal con mensaje simple
    header('Location: /antiprocastrinacion/?google=connected');
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Error en callback OAuth: ' . htmlspecialchars($e->getMessage());
}
