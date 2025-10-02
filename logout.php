<?php
require_once __DIR__ . '/src/Auth.php';
Auth::logout();
header('Location: ' . rtrim(AuthConfig::BASE_PATH, '/') . '/login.php');
exit;
