<?php
/**
 * Modelo para gestión de tareas
 */

require_once __DIR__ . '/Database.php';

class Task {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Crea una nueva tarea
     */
    public function create($title, $description = '', $parentId = null, $priority = 'medium') {
        // Determinar el nivel de columna basado en el padre
        $columnLevel = 1;
        if ($parentId !== null) {
            $parent = $this->getById($parentId);
            if ($parent) {
                $columnLevel = $parent['column_level'] + 1;
            }
        }
        
        // Obtener la siguiente posición en orden
        $position = $this->getNextPosition($parentId, $columnLevel);
        
        $sql = "INSERT INTO tasks (title, description, parent_id, column_level, position_order, priority) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->query($sql, [
            $title, 
            $description, 
            $parentId, 
            $columnLevel, 
            $position, 
            $priority
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Obtiene una tarea por ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM tasks WHERE id = ?";
        $stmt = $this->db->query($sql, [$id]);
        return $stmt->fetch();
    }
    
    /**
     * Obtiene todas las tareas padre (nivel 1)
     */
    public function getRootTasks() {
        $sql = "SELECT * FROM tasks WHERE parent_id IS NULL ORDER BY position_order ASC";
        $stmt = $this->db->query($sql);
        $rootTasks = $stmt->fetchAll();
        
        // Agregar información sobre si cada tarea raíz tiene hijos
        foreach ($rootTasks as &$task) {
            $children = $this->db->query(
                "SELECT COUNT(*) as count FROM tasks WHERE parent_id = ?", 
                [$task['id']]
            )->fetch();
            $task['has_children'] = $children['count'] > 0;
        }
        
        return $rootTasks;
    }
    
    /**
     * Obtiene las subtareas de una tarea padre
     */
    public function getChildTasks($parentId) {
        $sql = "SELECT * FROM tasks WHERE parent_id = ? ORDER BY position_order ASC";
        $stmt = $this->db->query($sql, [$parentId]);
        $children = $stmt->fetchAll();
        
        // Agregar información sobre si cada hijo tiene sus propios hijos
        foreach ($children as &$child) {
            $grandChildren = $this->db->query(
                "SELECT COUNT(*) as count FROM tasks WHERE parent_id = ?", 
                [$child['id']]
            )->fetch();
            $child['has_children'] = $grandChildren['count'] > 0;
        }
        
        return $children;
    }
    
    /**
     * Obtiene tareas por nivel de columna
     */
    public function getTasksByLevel($level) {
        $sql = "SELECT * FROM tasks WHERE column_level = ? ORDER BY position_order ASC";
        $stmt = $this->db->query($sql, [$level]);
        return $stmt->fetchAll();
    }
    
    /**
     * Actualiza una tarea
     */
    public function update($id, $title, $description = '', $priority = 'medium') {
        $sql = "UPDATE tasks SET title = ?, description = ?, priority = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?";
        $stmt = $this->db->query($sql, [$title, $description, $priority, $id]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Marca una tarea como completada
     */
    public function markCompleted($id) {
        $sql = "UPDATE tasks SET is_completed = TRUE, completed_at = CURRENT_TIMESTAMP 
                WHERE id = ?";
        $stmt = $this->db->query($sql, [$id]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Marca una tarea como no completada
     */
    public function markIncomplete($id) {
        $sql = "UPDATE tasks SET is_completed = FALSE, completed_at = NULL 
                WHERE id = ?";
        $stmt = $this->db->query($sql, [$id]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Elimina una tarea y todas sus subtareas
     */
    public function delete($id) {
        $sql = "DELETE FROM tasks WHERE id = ?";
        $stmt = $this->db->query($sql, [$id]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Cambia el orden de una tarea
     */
    public function updatePosition($id, $newPosition) {
        $sql = "UPDATE tasks SET position_order = ? WHERE id = ?";
        $stmt = $this->db->query($sql, [$newPosition, $id]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Obtiene la siguiente posición en orden para una tarea
     */
    private function getNextPosition($parentId, $columnLevel) {
        if ($parentId === null) {
            $sql = "SELECT COALESCE(MAX(position_order), 0) + 1 as next_position 
                    FROM tasks WHERE parent_id IS NULL";
            $stmt = $this->db->query($sql);
        } else {
            $sql = "SELECT COALESCE(MAX(position_order), 0) + 1 as next_position 
                    FROM tasks WHERE parent_id = ?";
            $stmt = $this->db->query($sql, [$parentId]);
        }
        
        $result = $stmt->fetch();
        return $result['next_position'];
    }
    
    /**
     * Obtiene estadísticas de tareas
     */
    public function getStats() {
        $sql = "SELECT 
                    COUNT(*) as total_tasks,
                    COUNT(CASE WHEN is_completed = TRUE THEN 1 END) as completed_tasks,
                    COUNT(CASE WHEN is_completed = FALSE THEN 1 END) as pending_tasks,
                    MAX(column_level) as max_depth
                FROM tasks";
        $stmt = $this->db->query($sql);
        return $stmt->fetch();
    }
    
    /**
     * Busca tareas por término
     */
    public function search($term) {
        $sql = "SELECT * FROM tasks WHERE title LIKE ? OR description LIKE ? 
                ORDER BY column_level ASC, position_order ASC";
        $searchTerm = '%' . $term . '%';
        $stmt = $this->db->query($sql, [$searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtiene la estructura completa de tareas para visualización en columnas
     */
    public function getColumnStructure() {
        $rootTasks = $this->getRootTasks();
        
        // Agregar información sobre si tienen hijos
        foreach ($rootTasks as &$task) {
            $children = $this->getChildTasks($task['id']);
            $task['has_children'] = count($children) > 0;
        }
        
        return $rootTasks;
    }
    
    /**
     * Obtiene las tareas hijas con información básica
     */
    private function buildTaskTree($task) {
        $children = $this->getChildTasks($task['id']);
        $task['has_children'] = count($children) > 0;
        $task['children'] = [];
        
        // Solo agregar información básica de los hijos, no recursión completa
        foreach ($children as $child) {
            $child['has_children'] = count($this->getChildTasks($child['id'])) > 0;
            $child['children'] = [];
            $task['children'][] = $child;
        }
        
        return $task;
    }
    
    /**
     * Obtiene el árbol completo de una tarea para impresión
     */
    public function getTaskTree($taskId) {
        $task = $this->getById($taskId);
        
        if (!$task) {
            return null;
        }
        
        return $this->buildCompleteTaskTree($task);
    }
    
    /**
     * Construye recursivamente el árbol completo de tareas para impresión
     */
    private function buildCompleteTaskTree($task) {
        $task['children'] = [];
        $children = $this->getChildTasks($task['id']);
        
        foreach ($children as $child) {
            $task['children'][] = $this->buildCompleteTaskTree($child);
        }
        
        return $task;
    }
}