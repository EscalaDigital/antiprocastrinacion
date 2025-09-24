<?php
/**
 * Controlador de API para operaciones AJAX
 */

require_once __DIR__ . '/src/Task.php';
require_once __DIR__ . '/src/GoogleService.php';

header('Content-Type: application/json');

$taskModel = new Task();
$response = ['success' => false, 'message' => '', 'data' => null];

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'create_from_gmail':
            $gmailInput = $_POST['gmail'] ?? '';
            $parentId = $_POST['parent_id'] ?? null;
            if (!$gmailInput) {
                throw new Exception('Falta el parámetro gmail (URL o ID)');
            }
            $google = new GoogleService();
            if (!$google->isConnected()) {
                throw new Exception('Conecta con Google primero');
            }
            $parsed = GoogleService::parseGmailUrl($gmailInput);
            $message = null;
            $gmailThreadId = null;
            $gmailMessageId = null;
            try {
                if (!empty($parsed['thread_id'])) {
                    $gmailThreadId = $parsed['thread_id'];
                    $message = $google->getGmailThreadFirstMessage($gmailThreadId);
                } else {
                    // Asumimos messageId directo
                    $gmailMessageId = $gmailInput;
                    $message = $google->getGmailMessageById($gmailMessageId);
                }
            } catch (Throwable $e) {
                throw new Exception('No se pudo leer el correo de Gmail: ' . $e->getMessage());
            }

            // Extraer asunto, from y fecha
            $subject = 'Correo sin asunto';
            $from = '';
            $date = '';
            if ($message) {
                $gmailMessageId = $gmailMessageId ?: $message->getId();
                $gmailThreadId = $gmailThreadId ?: $message->getThreadId();
                $headers = $message->getPayload()->getHeaders();
                foreach ($headers as $h) {
                    $name = strtolower($h->getName());
                    if ($name === 'subject') $subject = $h->getValue();
                    if ($name === 'from') $from = $h->getValue();
                    if ($name === 'date') $date = $h->getValue();
                }
            }

            $gmailUrl = $gmailThreadId ? ('https://mail.google.com/mail/u/0/#all/' . $gmailThreadId) : '';
            $descParts = [];
            if ($from) $descParts[] = 'De: ' . $from;
            if ($date) $descParts[] = 'Fecha: ' . $date;
            if ($gmailUrl) $descParts[] = 'Gmail: ' . $gmailUrl;
            $description = implode("\n", $descParts);

            $newId = $taskModel->create($subject, $description, $parentId ?: null, 'medium');
            // Guardar IDs de Gmail
            $taskModel->updateGmailRefs($newId, $gmailMessageId, $gmailThreadId);

            $response = [
                'success' => true,
                'message' => 'Tarea creada desde Gmail',
                'data' => ['id' => $newId]
            ];
            break;

        case 'create_google_task':
            $id = $_POST['id'] ?? 0;
            $t = $taskModel->getById($id);
            if (!$t) throw new Exception('Tarea no encontrada');
            $google = new GoogleService();
            if (!$google->isConnected()) throw new Exception('Conecta con Google primero');
            $gtaskId = $google->createGoogleTask($t['title'], (string)($t['description'] ?? ''));
            if ($gtaskId) {
                $taskModel->updateGoogleTaskId($id, $gtaskId);
                $response = ['success' => true, 'message' => 'Creada en Google Tasks', 'data' => ['google_tasks_id' => $gtaskId]];
            } else {
                throw new Exception('No se pudo crear en Google Tasks');
            }
            break;

        case 'create_calendar_event':
            $id = $_POST['id'] ?? 0;
            $t = $taskModel->getById($id);
            if (!$t) throw new Exception('Tarea no encontrada');
            $startIso = $_POST['start'] ?? null;
            $endIso = $_POST['end'] ?? null;
            $allDay = !empty($_POST['all_day']);
            $reminderMinutes = isset($_POST['reminder_minutes']) && $_POST['reminder_minutes'] !== '' ? (int)$_POST['reminder_minutes'] : null;
            $location = isset($_POST['location']) ? trim((string)$_POST['location']) : null;
            $notes = isset($_POST['notes']) ? trim((string)$_POST['notes']) : '';

            $desc = (string)($t['description'] ?? '');
            if ($notes !== '') {
                $desc = $desc ? ($desc . "\n\nNotas:\n" . $notes) : $notes;
            }

            $google = new GoogleService();
            if (!$google->isConnected()) throw new Exception('Conecta con Google primero');

            $options = [
                'all_day' => $allDay,
                'reminder_minutes' => $reminderMinutes,
                'location' => $location,
            ];

            if ($allDay) {
                $startDate = $startIso ? substr($startIso, 0, 10) : date('Y-m-d');
                $endDate = date('Y-m-d', strtotime($startDate . ' +1 day'));
                $options['start_date'] = $startDate;
                $options['end_date'] = $endDate;
                $eventId = $google->createCalendarEvent($t['title'], $desc, null, null, $options);
            } else {
                $start = $startIso ? new DateTime($startIso) : new DateTime();
                $end = $endIso ? new DateTime($endIso) : (new DateTime('+1 hour'));
                $eventId = $google->createCalendarEvent($t['title'], $desc, $start, $end, $options);
            }

            if ($eventId) {
                $taskModel->updateGoogleCalendarId($id, $eventId);
                $response = ['success' => true, 'message' => 'Evento creado en Calendar', 'data' => ['google_calendar_event_id' => $eventId]];
            } else {
                throw new Exception('No se pudo crear el evento en Calendar');
            }
            break;
        case 'create':
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $gmailUrl = $_POST['gmail_url'] ?? '';
            $parentId = $_POST['parent_id'] ?? null;
            $priority = $_POST['priority'] ?? 'medium';
            
            if (empty($title)) {
                throw new Exception('El título es requerido');
            }
            
            $taskId = $taskModel->create($title, $description, $parentId, $priority);
            if ($gmailUrl !== '') {
                $taskModel->updateGmailUrl($taskId, $gmailUrl);
            }

            // Si viene una URL de Gmail, parsear y guardar referencias
            if (!empty($gmailUrl)) {
                $parsed = GoogleService::parseGmailUrl($gmailUrl);
                $gmailThreadId = $parsed['thread_id'] ?? null;
                $gmailMessageId = $parsed['message_id'] ?? null;
                $taskModel->updateGmailRefs($taskId, $gmailMessageId, $gmailThreadId);
            }
            $response = [
                'success' => true,
                'message' => 'Tarea creada exitosamente',
                'data' => ['id' => $taskId]
            ];
            break;
            
        case 'get':
            $id = $_GET['id'] ?? 0;
            $task = $taskModel->getById($id);
            
            if (!$task) {
                throw new Exception('Tarea no encontrada');
            }
            
            $response = [
                'success' => true,
                'data' => $task
            ];
            break;
            
        case 'update':
            $id = $_POST['id'] ?? 0;
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $gmailUrl = $_POST['gmail_url'] ?? '';
            $priority = $_POST['priority'] ?? 'medium';
            
            if (empty($title)) {
                throw new Exception('El título es requerido');
            }
            
            $updated = $taskModel->update($id, $title, $description, $priority);
            
            if ($updated) {
                if ($gmailUrl !== '') {
                    $taskModel->updateGmailUrl($id, $gmailUrl);
                }
                if ($gmailUrl !== '') {
                    $parsed = GoogleService::parseGmailUrl($gmailUrl);
                    $gmailThreadId = $parsed['thread_id'] ?? null;
                    $gmailMessageId = $parsed['message_id'] ?? null;
                    $taskModel->updateGmailRefs($id, $gmailMessageId, $gmailThreadId);
                }
                $response = [
                    'success' => true,
                    'message' => 'Tarea actualizada exitosamente'
                ];
            } else {
                throw new Exception('No se pudo actualizar la tarea');
            }
            break;
            
        case 'delete':
            $id = $_POST['id'] ?? 0;
            $deleted = $taskModel->delete($id);
            
            if ($deleted) {
                $response = [
                    'success' => true,
                    'message' => 'Tarea eliminada exitosamente'
                ];
            } else {
                throw new Exception('No se pudo eliminar la tarea');
            }
            break;
            
        case 'toggle_complete':
            $id = $_POST['id'] ?? 0;
            $isCompleted = $_POST['is_completed'] ?? false;
            
            if ($isCompleted === 'true' || $isCompleted === true) {
                $result = $taskModel->markCompleted($id);
            } else {
                $result = $taskModel->markIncomplete($id);
            }
            
            if ($result) {
                $response = [
                    'success' => true,
                    'message' => 'Estado de tarea actualizado'
                ];
            } else {
                throw new Exception('No se pudo actualizar el estado');
            }
            break;
            
        case 'get_children':
            $parentId = $_GET['parent_id'] ?? 0;
            $children = $taskModel->getChildTasks($parentId);
            
            $response = [
                'success' => true,
                'data' => $children
            ];
            break;
            
        case 'get_root_tasks':
            $rootTasks = $taskModel->getRootTasks();
            
            $response = [
                'success' => true,
                'data' => $rootTasks
            ];
            break;
            
        case 'get_column_structure':
            $structure = $taskModel->getColumnStructure();
            
            $response = [
                'success' => true,
                'data' => $structure
            ];
            break;
            
        case 'get_task_tree':
            $id = $_GET['id'] ?? 0;
            $taskTree = $taskModel->getTaskTree($id);
            
            if (!$taskTree) {
                throw new Exception('Tarea no encontrada');
            }
            
            $response = [
                'success' => true,
                'data' => $taskTree
            ];
            break;
            
        case 'search':
            $term = $_GET['term'] ?? '';
            if (empty($term)) {
                throw new Exception('Término de búsqueda requerido');
            }
            
            $results = $taskModel->search($term);
            
            $response = [
                'success' => true,
                'data' => $results
            ];
            break;
            
        case 'get_stats':
            $stats = $taskModel->getStats();
            
            $response = [
                'success' => true,
                'data' => $stats
            ];
            break;
            
        case 'update_position':
            $id = $_POST['id'] ?? 0;
            $position = $_POST['position'] ?? 0;
            
            $updated = $taskModel->updatePosition($id, $position);
            
            if ($updated) {
                $response = [
                    'success' => true,
                    'message' => 'Posición actualizada'
                ];
            } else {
                throw new Exception('No se pudo actualizar la posición');
            }
            break;
        
        case 'update_parent':
            $id = $_POST['id'] ?? 0;
            // parent_id puede ser null, string vacío, o un número
            $parentId = array_key_exists('parent_id', $_POST) ? $_POST['parent_id'] : null;
            if ($parentId === '' || $parentId === 'null') { $parentId = null; }

            if (!$id) throw new Exception('ID requerido');
            try {
                $ok = $taskModel->updateParent($id, $parentId);
                if ($ok) {
                    $updatedTask = $taskModel->getById($id);
                    $response = [
                        'success' => true,
                        'message' => 'Padre actualizado',
                        'data' => $updatedTask
                    ];
                } else {
                    throw new Exception('No se pudo reubicar la tarea');
                }
            } catch (Exception $ex) {
                throw new Exception($ex->getMessage());
            }
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);