<?php
require_once __DIR__ . '/src/Auth.php';
Auth::init();

// Si ya está logueado, ir a index
if (Auth::isLoggedIn()) {
    header('Location: ' . rtrim(AuthConfig::BASE_PATH, '/') . '/index.php');
    exit;
}

$error = '';
$redirect = isset($_GET['r']) ? (string)$_GET['r'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $remember = !empty($_POST['remember']);
    $redirect = (string)($_POST['redirect'] ?? '');

    if (Auth::attemptLogin($username, $password, $remember)) {
        // Redirigir a donde venía o a index
        $dest = $redirect !== '' ? $redirect : (rtrim(AuthConfig::BASE_PATH, '/') . '/index.php');
        header('Location: ' . $dest);
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Iniciar sesión - Antiprocrastinación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h4 class="mb-3 text-center">Iniciar sesión</h4>
                        <?php if ($error): ?>
                            <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <form method="post">
                            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>" />
                            <div class="mb-3">
                                <label class="form-label">Usuario</label>
                                <input type="text" class="form-control" name="username" required autofocus />
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contraseña</label>
                                <input type="password" class="form-control" name="password" required />
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" />
                                <label class="form-check-label" for="remember">Recordarme</label>
                            </div>
                            <div class="d-grid">
                                <button class="btn btn-primary" type="submit">Entrar</button>
                            </div>
                        </form>
                    </div>
                </div>
                <p class="text-center text-muted small mt-3">Uso personal - acceso privado</p>
            </div>
        </div>
    </div>
</body>
</html>
