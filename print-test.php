<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Impresión Individual y PDF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .task-example {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .priority-high { border-left: 4px solid #dc3545; }
        .priority-medium { border-left: 4px solid #ffc107; }
        .priority-low { border-left: 4px solid #198754; }
        .feature-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2>🖨️ Nueva Funcionalidad: Impresión Individual y PDF</h2>
        
        <div class="alert alert-success">
            <strong>¡Actualización!</strong> Ahora puedes imprimir tareas individuales sin subtareas y generar PDFs profesionales.
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="feature-card">
                    <h4>📄 Solo esta tarea</h4>
                    <p>Imprime únicamente la tarea seleccionada con formato optimizado, sin subtareas ni jerarquías.</p>
                    <ul class="list-unstyled">
                        <li>✅ Formato limpio y profesional</li>
                        <li>✅ Información completa de la tarea</li>
                        <li>✅ Estado visual claro</li>
                        <li>✅ Metadatos organizados</li>
                    </ul>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="feature-card">
                    <h4>📱 Generar PDF</h4>
                    <p>Crea un archivo PDF descargable con la información de la tarea individual.</p>
                    <ul class="list-unstyled">
                        <li>✅ Archivo PDF descargable</li>
                        <li>✅ Formato profesional</li>
                        <li>✅ Compatible con cualquier dispositivo</li>
                        <li>✅ Fácil de compartir</li>
                    </ul>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="feature-card">
                    <h4>🌳 Con subtareas</h4>
                    <p>Imprime la tarea junto con toda su jerarquía de subtareas (funcionalidad anterior).</p>
                    <ul class="list-unstyled">
                        <li>✅ Tarea principal + subtareas</li>
                        <li>✅ Jerarquía visual</li>
                        <li>✅ Estructura completa</li>
                        <li>✅ Ideal para proyectos</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <h3>🎯 Nuevo menú desplegable de impresión:</h3>
        <div class="d-flex gap-2 my-3 justify-content-center">
            <button class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></button>
            <button class="btn btn-sm btn-outline-success"><i class="fas fa-plus"></i></button>
            
            <div class="btn-group" role="group">
                <button class="btn btn-sm btn-outline-info dropdown-toggle" data-bs-toggle="dropdown" title="Opciones de impresión">
                    <i class="fas fa-print"></i>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">
                        <i class="fas fa-file-alt me-2"></i>Solo esta tarea
                    </a></li>
                    <li><a class="dropdown-item" href="#">
                        <i class="fas fa-file-pdf me-2"></i>Generar PDF
                    </a></li>
                    <li><a class="dropdown-item" href="#">
                        <i class="fas fa-sitemap me-2"></i>Con subtareas
                    </a></li>
                </ul>
            </div>
            
            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
        </div>
        
        <h3>📋 Ejemplo de formato individual:</h3>
        <div class="task-example priority-medium" style="max-width: 600px; margin: 20px auto;">
            <div style="text-align: center; border-bottom: 2px solid #0d6efd; padding-bottom: 10px; margin-bottom: 20px;">
                <h4 style="color: #0d6efd; margin: 0;">📋 Antiprocrastinación</h4>
                <small style="color: #666;">Tarea individual impresa el <?php echo date('d/m/Y'); ?> a las <?php echo date('H:i'); ?></small>
            </div>
            
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                <div style="background: #fff3cd; padding: 5px 15px; border-radius: 15px; display: inline-block; margin-bottom: 15px;">
                    <strong>⏳ PENDIENTE</strong>
                </div>
                
                <h3 style="color: #2c3e50; margin: 15px 0;">Completar proyecto web</h3>
                
                <div style="background: white; padding: 15px; border-radius: 5px; border-left: 4px solid #0d6efd; margin: 15px 0;">
                    <strong>Descripción:</strong><br>
                    Desarrollar una aplicación completa de gestión de tareas con todas las funcionalidades requeridas.
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
                    <div style="background: white; padding: 10px; border-radius: 5px; border: 1px solid #dee2e6;">
                        <strong style="color: #495057;">🎯 Prioridad</strong><br>
                        <span style="color: #6c757d;">Media</span>
                    </div>
                    <div style="background: white; padding: 10px; border-radius: 5px; border: 1px solid #dee2e6;">
                        <strong style="color: #495057;">📊 Estado</strong><br>
                        <span style="color: #6c757d;">Pendiente</span>
                    </div>
                </div>
            </div>
        </div>
        
        <h3>🚀 Cómo usar:</h3>
        <ol>
            <li>Ve a la aplicación principal: <a href="index.php" class="btn btn-primary btn-sm">Abrir aplicación</a></li>
            <li>Busca cualquier tarea en las columnas</li>
            <li>Haz clic en el botón de impresión <button class="btn btn-sm btn-outline-info"><i class="fas fa-print"></i></button></li>
            <li>Selecciona la opción que prefieras:
                <ul>
                    <li><strong>Solo esta tarea:</strong> Imprime únicamente la tarea individual</li>
                    <li><strong>Generar PDF:</strong> Crea y descarga un archivo PDF</li>
                    <li><strong>Con subtareas:</strong> Imprime con toda la jerarquía</li>
                </ul>
            </li>
        </ol>
        
        <div class="alert alert-info">
            <strong>💡 Nota:</strong> El PDF se genera usando la librería jsPDF y se descarga automáticamente con un nombre descriptivo basado en el título de la tarea.
        </div>
        
        <div class="alert alert-warning">
            <strong>⚠️ Requisitos:</strong> Para generar PDFs, la aplicación carga automáticamente la librería jsPDF desde CDN. Si no hay conexión a internet, se usará la impresión normal.
        </div>
        
        <hr>
        <div class="text-center">
            <a href="index.php" class="btn btn-primary">← Volver a la aplicación</a>
            <a href="test.php" class="btn btn-secondary">Verificar configuración</a>
            <a href="debug.php" class="btn btn-info">Debug de tareas</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>