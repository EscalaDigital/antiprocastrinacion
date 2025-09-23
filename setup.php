<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Antiprocrastinación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-cog me-2"></i>
                            Configuración Inicial - Antiprocrastinación
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>¡Bienvenido!</strong> Para comenzar a usar la aplicación, necesitas configurar la base de datos.
                        </div>

                        <h5>Pasos para la configuración:</h5>
                        
                        <div class="accordion" id="setupAccordion">
                            <!-- Paso 1 -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#step1">
                                        <i class="fas fa-database me-2"></i>
                                        Paso 1: Configurar la base de datos
                                    </button>
                                </h2>
                                <div id="step1" class="accordion-collapse collapse show">
                                    <div class="accordion-body">
                                        <p>Ejecuta los siguientes comandos en phpMyAdmin o desde la línea de comandos de MySQL:</p>
                                        <div class="bg-dark text-light p-3 rounded">
                                            <code>
                                                # Accede a phpMyAdmin en: http://localhost/phpmyadmin<br>
                                                # O desde la línea de comandos:<br>
                                                mysql -u root -p<br><br>
                                                # Luego ejecuta el archivo SQL:
                                            </code>
                                        </div>
                                        <p class="mt-3">
                                            <strong>Archivo SQL:</strong> 
                                            <code>config/schema.sql</code>
                                        </p>
                                        <div class="alert alert-warning">
                                            <small>
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                Asegúrate de que XAMPP esté ejecutándose y MySQL esté activo.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Paso 2 -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step2">
                                        <i class="fas fa-wrench me-2"></i>
                                        Paso 2: Verificar configuración
                                    </button>
                                </h2>
                                <div id="step2" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <p>Verifica que la configuración en <code>config/database.php</code> sea correcta:</p>
                                        <ul>
                                            <li><strong>Host:</strong> localhost</li>
                                            <li><strong>Base de datos:</strong> antiprocrastinacion</li>
                                            <li><strong>Usuario:</strong> root</li>
                                            <li><strong>Contraseña:</strong> (vacía por defecto en XAMPP)</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Paso 3 -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step3">
                                        <i class="fas fa-play me-2"></i>
                                        Paso 3: Iniciar la aplicación
                                    </button>
                                </h2>
                                <div id="step3" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <p>Una vez configurada la base de datos, recarga esta página para acceder a la aplicación.</p>
                                        <button class="btn btn-success" onclick="window.location.reload()">
                                            <i class="fas fa-refresh me-2"></i>
                                            Recargar página
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <h5>Características de la aplicación:</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success me-2"></i>Gestión de micro-tareas</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Columnas dinámicas</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Atajos de teclado</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success me-2"></i>Función de impresión</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Búsqueda rápida</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Estadísticas</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-light mt-4">
                            <h6>Inspirado en Colonnes</h6>
                            <p class="mb-0">
                                Esta aplicación está inspirada en <a href="https://www.colonnes.com/" target="_blank">Colonnes</a>, 
                                una herramienta para superar la procrastinación mediante la gestión de micro-tareas.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>