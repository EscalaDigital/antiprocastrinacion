<?php
/**
 * Script de prueba para verificar la configuración
 */
require_once __DIR__ . '/src/Auth.php';
Auth::requireLogin();

echo "<h2>Verificación de la configuración</h2>";

// Verificar que PHP funciona
echo "<p>✅ PHP está funcionando (versión: " . phpversion() . ")</p>";

// Verificar la ruta de archivos
echo "<p>Directorio actual: " . __DIR__ . "</p>";

// Verificar si el archivo Database.php existe
$databaseFile = __DIR__ . '/src/Database.php';
if (file_exists($databaseFile)) {
    echo "<p>✅ Archivo Database.php encontrado: $databaseFile</p>";
} else {
    echo "<p>❌ Archivo Database.php NO encontrado: $databaseFile</p>";
}

// Verificar si el archivo Task.php existe
$taskFile = __DIR__ . '/src/Task.php';
if (file_exists($taskFile)) {
    echo "<p>✅ Archivo Task.php encontrado: $taskFile</p>";
} else {
    echo "<p>❌ Archivo Task.php NO encontrado: $taskFile</p>";
}

// Verificar la configuración de la base de datos
$configFile = __DIR__ . '/config/database.php';
if (file_exists($configFile)) {
    echo "<p>✅ Archivo de configuración encontrado: $configFile</p>";
    
    require_once $configFile;
    
    try {
        $dsn = DatabaseConfig::getDSN();
        echo "<p>✅ Configuración de BD leída correctamente</p>";
        echo "<p>DSN: $dsn</p>";
        
        // Intentar conectar
        $pdo = new PDO(
            $dsn,
            DatabaseConfig::USERNAME,
            DatabaseConfig::PASSWORD,
            DatabaseConfig::getOptions()
        );
        echo "<p>✅ Conexión a la base de datos exitosa</p>";
        
        // Verificar si la tabla existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'tasks'");
        if ($stmt->rowCount() > 0) {
            echo "<p>✅ Tabla 'tasks' encontrada</p>";
            
            // Contar tareas
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM tasks");
            $result = $stmt->fetch();
            echo "<p>📊 Número de tareas en la base de datos: " . $result['count'] . "</p>";
        } else {
            echo "<p>❌ Tabla 'tasks' no encontrada. Ejecuta el archivo schema.sql</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p>❌ Error de conexión a la base de datos: " . $e->getMessage() . "</p>";
        echo "<p>💡 Asegúrate de que MySQL esté ejecutándose y la base de datos 'antiprocrastinacion' exista</p>";
    }
} else {
    echo "<p>❌ Archivo de configuración NO encontrado: $configFile</p>";
}

// Verificar archivos de assets
$cssFile = __DIR__ . '/assets/css/style.css';
$jsFile = __DIR__ . '/assets/js/app.js';

if (file_exists($cssFile)) {
    echo "<p>✅ Archivo CSS encontrado</p>";
} else {
    echo "<p>❌ Archivo CSS NO encontrado: $cssFile</p>";
}

if (file_exists($jsFile)) {
    echo "<p>✅ Archivo JavaScript encontrado</p>";
} else {
    echo "<p>❌ Archivo JavaScript NO encontrado: $jsFile</p>";
}

echo "<hr>";
echo "<p><strong>Si todos los elementos muestran ✅, puedes acceder a la aplicación en:</strong></p>";
echo "<p><a href='index.php'>http://localhost/antiprocastrinacion/index.php</a></p>";
echo "<p><em>Si hay errores ❌, revisa los archivos faltantes o la configuración de la base de datos.</em></p>";
?>