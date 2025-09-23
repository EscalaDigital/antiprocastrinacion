<?php
/**
 * Clase para manejo de conexión a la base de datos
 */

require_once __DIR__ . '/../config/database.php';

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $this->connection = new PDO(
                DatabaseConfig::getDSN(),
                DatabaseConfig::USERNAME,
                DatabaseConfig::PASSWORD,
                DatabaseConfig::getOptions()
            );
        } catch (PDOException $e) {
            die('Error de conexión a la base de datos: ' . $e->getMessage());
        }
    }
    
    /**
     * Singleton pattern para obtener la instancia de la base de datos
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obtiene la conexión PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Ejecuta una consulta preparada
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log('Error en consulta SQL: ' . $e->getMessage());
            throw new Exception('Error en la base de datos');
        }
    }
    
    /**
     * Obtiene el último ID insertado
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
}