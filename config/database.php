<?php
/**
 * Configuración de la base de datos
 */

class DatabaseConfig {
    // Configuración para XAMPP local
    const HOST = 'localhost';
    const DB_NAME = 'antiprocrastinacion';
    const USERNAME = 'root';
    const PASSWORD = '';
    const CHARSET = 'utf8mb4';
    
    /**
     * Obtiene la cadena de conexión PDO
     */
    public static function getDSN() {
        return sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            self::HOST,
            self::DB_NAME,
            self::CHARSET
        );
    }
    
    /**
     * Opciones por defecto para PDO
     */
    public static function getOptions() {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
    }
}