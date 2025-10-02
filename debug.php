<?php
/**
 * Script de prueba para verificar las funciones corregidas
 */

require_once __DIR__ . '/src/Auth.php';
Auth::requireLogin();
require_once __DIR__ . '/src/Task.php';

header('Content-Type: application/json');

echo "<h2>Prueba de funciones corregidas</h2>";

try {
    $taskModel = new Task();
    
    echo "<h3>1. Probando getRootTasks():</h3>";
    $rootTasks = $taskModel->getRootTasks();
    echo "<pre>";
    foreach ($rootTasks as $task) {
        echo "ID: {$task['id']}, Título: {$task['title']}, Tiene hijos: " . 
             ($task['has_children'] ? 'Sí' : 'No') . "\n";
    }
    echo "</pre>";
    
    echo "<h3>2. Probando getColumnStructure():</h3>";
    $structure = $taskModel->getColumnStructure();
    echo "<pre>";
    print_r($structure);
    echo "</pre>";
    
    if (!empty($rootTasks)) {
        $firstTask = $rootTasks[0];
        echo "<h3>3. Probando getChildTasks() para tarea ID {$firstTask['id']}:</h3>";
        $children = $taskModel->getChildTasks($firstTask['id']);
        echo "<pre>";
        foreach ($children as $child) {
            echo "ID: {$child['id']}, Título: {$child['title']}, Tiene hijos: " . 
                 ($child['has_children'] ? 'Sí' : 'No') . "\n";
        }
        echo "</pre>";
    }
    
    echo "<h3>4. Probando estadísticas:</h3>";
    $stats = $taskModel->getStats();
    echo "<pre>";
    print_r($stats);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Volver a la aplicación</a></p>";
?>