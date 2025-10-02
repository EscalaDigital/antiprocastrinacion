<?php
/**
 * Archivo principal de la aplicación
 * Redirige a index.html y maneja la configuración inicial
 */

// Autenticación (protección de acceso)
require_once __DIR__ . '/src/Auth.php';
Auth::requireLogin();

// Verificar si la base de datos existe y está configurada
require_once __DIR__ . '/src/Database.php';

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    // Verificar si las tablas existen
    $stmt = $connection->query("SHOW TABLES LIKE 'tasks'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        // Mostrar página de configuración
        include 'setup.php';
        exit;
    }
    
} catch (Exception $e) {
    // Error de conexión - mostrar página de configuración
    include 'setup.php';
    exit;
}

// Si todo está bien, mostrar la aplicación
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Antiprocrastinación - Gestor de Micro-tareas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-columns me-2"></i>Antiprocrastinación
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="btn btn-outline-light me-2" href="/antiprocastrinacion/api/google/auth.php">
                    <i class="fab fa-google me-1"></i> Conectar Google
                </a>
                <div class="nav-item dropdown">
                    <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-cog"></i> Opciones
                    </button>
                    <ul class="dropdown-menu">
                        <li class="dropdown-item-text text-muted">
                            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>
                        </li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-right-from-bracket me-2"></i>Salir</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" onclick="showStats()"><i class="fas fa-chart-bar me-2"></i>Estadísticas</a></li>
                        <li><a class="dropdown-item" href="#" onclick="exportTasks()"><i class="fas fa-download me-2"></i>Exportar</a></li>
                        <li><a class="dropdown-item" href="#" onclick="printTasks()"><i class="fas fa-print me-2"></i>Imprimir</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" onclick="showHelp()"><i class="fas fa-question-circle me-2"></i>Ayuda</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" onclick="toggleTheme()"><i class="fas fa-moon me-2"></i>Alternar tema</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" onclick="taskManager.createTaskFromGmailPrompt()"><i class="fab fa-google me-2"></i>Crear tarea desde Gmail</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid mt-3">
        <!-- Barra de herramientas -->
        <div class="row mb-3 align-items-center g-2">
            <div class="col-md-5">
                <div class="input-group">
                    <input type="text" id="searchInput" class="form-control" placeholder="Buscar tareas...">
                    <button class="btn btn-outline-primary" type="button" onclick="searchTasks()">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-7 text-end">
                <div class="btn-group me-2">
                    <button class="btn btn-outline-warning" onclick="taskManager.quickUrgentFilter()" title="Ver urgentes (Alta)">
                        <i class="fas fa-bolt me-1"></i> Urgentes
                    </button>
                    <div class="btn-group">
                        <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-filter me-1"></i> Filtros
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-3" style="min-width: 320px;">
                            <div class="mb-2">
                                <div class="form-label mb-1">Prioridad</div>
                                <div class="d-flex gap-2">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="f-prio-high" value="high" checked>
                                        <label class="form-check-label text-danger" for="f-prio-high">Alta</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="f-prio-medium" value="medium" checked>
                                        <label class="form-check-label text-warning" for="f-prio-medium">Media</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="f-prio-low" value="low" checked>
                                        <label class="form-check-label text-success" for="f-prio-low">Baja</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label">Estado</label>
                                    <select id="f-status" class="form-select form-select-sm">
                                        <option value="all">Todos</option>
                                        <option value="pending">Pendientes</option>
                                        <option value="completed">Completadas</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Con Gmail</label>
                                    <select id="f-gmail" class="form-select form-select-sm">
                                        <option value="any">Cualquiera</option>
                                        <option value="1">Sí</option>
                                        <option value="0">No</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label">Nivel mín.</label>
                                    <input id="f-level-min" type="number" class="form-control form-control-sm" min="1" placeholder="1">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Nivel máx.</label>
                                    <input id="f-level-max" type="number" class="form-control form-control-sm" min="1" placeholder="5">
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Ordenar por</label>
                                <select id="f-order" class="form-select form-select-sm">
                                    <option value="priority">Prioridad</option>
                                    <option value="updated_desc">Actualización (recientes primero)</option>
                                    <option value="created_desc">Creación (recientes primero)</option>
                                    <option value="created_asc">Creación (antiguas primero)</option>
                                    <option value="position">Posición/Nivel</option>
                                </select>
                            </div>
                            <div class="d-flex justify-content-between">
                                <button class="btn btn-sm btn-outline-secondary" onclick="taskManager.clearFilters()">Limpiar</button>
                                <button class="btn btn-sm btn-primary" onclick="taskManager.applyFilters()">Aplicar</button>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="btn btn-success me-2" onclick="showCreateTaskModal()">
                    <i class="fas fa-plus me-2"></i>Nueva Tarea
                </button>
                <button class="btn btn-info btn-icon" onclick="loadTasks()" title="Recargar">
                    <i class="fas fa-rotate-right"></i>
                </button>
            </div>
        </div>

        <!-- Estadísticas rápidas -->
        <div class="row mb-3" id="statsRow" style="display: none;">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <h4 class="text-primary" id="totalTasks">0</h4>
                                <small class="text-muted">Total de tareas</small>
                            </div>
                            <div class="col-md-3">
                                <h4 class="text-success" id="completedTasks">0</h4>
                                <small class="text-muted">Completadas</small>
                            </div>
                            <div class="col-md-3">
                                <h4 class="text-warning" id="pendingTasks">0</h4>
                                <small class="text-muted">Pendientes</small>
                            </div>
                            <div class="col-md-3">
                                <h4 class="text-info" id="maxDepth">0</h4>
                                <small class="text-muted">Niveles máximos</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columnas de tareas -->
        <div class="row">
            <div class="col-12">
                <div id="columnsContainer" class="d-flex overflow-auto" style="min-height: 600px;">
                    <!-- Las columnas se generarán dinámicamente aquí -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para crear/editar tarea -->
    <div class="modal fade" id="taskModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="taskModalTitle">Nueva Tarea</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="taskForm">
                        <input type="hidden" id="taskId">
                        <input type="hidden" id="parentId">
                        
                        <div class="mb-3">
                            <label for="taskTitle" class="form-label">Título*</label>
                            <input type="text" class="form-control" id="taskTitle" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="taskDescription" class="form-label">Descripción</label>
                            <textarea class="form-control" id="taskDescription" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="taskGmailUrl" class="form-label">URL de Gmail</label>
                            <input type="url" class="form-control" id="taskGmailUrl" placeholder="Pega aquí la URL del correo (opcional)">
                            <div class="form-text">Si se añade, aparecerá un icono de email en la tarea.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="taskPriority" class="form-label">Prioridad</label>
                            <select class="form-select" id="taskPriority">
                                <option value="low">Baja</option>
                                <option value="medium" selected>Media</option>
                                <option value="high">Alta</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveTask()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de ayuda -->
    <div class="modal fade" id="helpModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ayuda - Atajos de teclado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Navegación</h6>
                            <ul class="list-unstyled">
                                <li><kbd>↑</kbd> / <kbd>↓</kbd> - Navegar tareas</li>
                                <li><kbd>←</kbd> / <kbd>→</kbd> - Cambiar columna</li>
                                <li><kbd>Enter</kbd> - Editar tarea seleccionada</li>
                                <li><kbd>Ctrl+N</kbd> - Nueva tarea</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Acciones</h6>
                            <ul class="list-unstyled">
                                <li><kbd>Space</kbd> - Marcar completa/incompleta</li>
                                <li><kbd>Del</kbd> - Eliminar tarea</li>
                                <li><kbd>Ctrl+F</kbd> - Buscar</li>
                                <li><kbd>Ctrl+P</kbd> - Imprimir</li>
                            </ul>
                        </div>
                    </div>
                    <hr>
                    <h6>Cómo usar la aplicación</h6>
                    <ol>
                        <li>Crea una tarea principal usando el botón "Nueva Tarea"</li>
                        <li>Selecciona la tarea y usa el botón "+" para crear subtareas</li>
                        <li>Repite el proceso para crear tantos niveles como necesites</li>
                        <li>Marca las tareas como completadas cuando las termines</li>
                        <li>Usa la función de impresión para tener tus tareas en papel</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal crear evento Calendar -->
    <div class="modal fade" id="calendarModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crear evento en Google Calendar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="calendarForm">
                        <input type="hidden" id="calendarTaskId">
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label">Fecha</label>
                                <input type="date" class="form-control" id="calDate" required>
                            </div>
                            <div class="col-3">
                                <label class="form-label">Inicio</label>
                                <input type="time" class="form-control" id="calStartTime" value="09:00" required>
                            </div>
                            <div class="col-3">
                                <label class="form-label">Fin</label>
                                <input type="time" class="form-control" id="calEndTime" value="10:00" required>
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label">Recordatorio (min)</label>
                                <input type="number" class="form-control" id="calReminder" value="30" min="0">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Ubicación</label>
                                <input type="text" class="form-control" id="calLocation" placeholder="Opcional">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Notas del evento</label>
                            <textarea class="form-control" id="calNotes" rows="3" placeholder="Opcional"></textarea>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="calAllDay">
                            <label class="form-check-label" for="calAllDay">Evento de todo el día</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="submitCalendarEvent()">Crear evento</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast para notificaciones -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="notificationToast" class="toast" role="alert">
            <div class="toast-header">
                <strong class="me-auto">Notificación</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body" id="toastMessage">
                <!-- Mensaje dinámico -->
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>