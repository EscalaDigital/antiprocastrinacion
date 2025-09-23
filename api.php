<?php
/**
 * Controlador de API para operaciones AJAX
 */

require_once __DIR__ . '/src/Task.php';

header('Content-Type: application/json');

$taskModel = new Task();
$response = ['success' => false, 'message' => '', 'data' => null];

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $parentId = $_POST['parent_id'] ?? null;
            $priority = $_POST['priority'] ?? 'medium';
            
            if (empty($title)) {
                throw new Exception('El título es requerido');
            }
            
            $taskId = $taskModel->create($title, $description, $parentId, $priority);
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
            $priority = $_POST['priority'] ?? 'medium';
            
            if (empty($title)) {
                throw new Exception('El título es requerido');
            }
            
            $updated = $taskModel->update($id, $title, $description, $priority);
            
            if ($updated) {
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