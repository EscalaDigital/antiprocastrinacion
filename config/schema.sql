-- Base de datos para Antiprocrastinación
CREATE DATABASE IF NOT EXISTS antiprocrastinacion CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE antiprocrastinacion;

-- Tabla de tareas con soporte para jerarquías
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    parent_id INT DEFAULT NULL,
    position_order INT DEFAULT 0,
    column_level INT DEFAULT 1,
    is_completed BOOLEAN DEFAULT FALSE,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    
    -- Clave foránea para tareas padre
    FOREIGN KEY (parent_id) REFERENCES tasks(id) ON DELETE CASCADE,
    
    -- Índices para mejorar el rendimiento
    INDEX idx_parent_id (parent_id),
    INDEX idx_column_level (column_level),
    INDEX idx_completed (is_completed),
    INDEX idx_position (position_order)
) ENGINE=InnoDB;

-- Tabla de configuración del usuario (para futuras características)
CREATE TABLE user_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insertar configuraciones por defecto
INSERT INTO user_settings (setting_key, setting_value) VALUES
('max_column_depth', '5'),
('auto_save', 'true'),
('keyboard_shortcuts', 'true'),
('print_format', 'thermal'),
('theme', 'light');

-- Datos de ejemplo para demostración
INSERT INTO tasks (title, description, parent_id, column_level, position_order) VALUES
('Completar proyecto web', 'Proyecto principal de desarrollo web', NULL, 1, 1),
('Aprender PHP avanzado', 'Estudiar conceptos avanzados de PHP', NULL, 1, 2),
('Ejercitar rutina diaria', 'Mantener hábitos de ejercicio', NULL, 1, 3);

-- Subtareas para "Completar proyecto web"
INSERT INTO tasks (title, description, parent_id, column_level, position_order) VALUES
('Diseñar base de datos', 'Crear esquema y relaciones', 1, 2, 1),
('Implementar backend', 'Desarrollar lógica de servidor', 1, 2, 2),
('Crear interfaz usuario', 'Desarrollar frontend responsivo', 1, 2, 3);

-- Micro-tareas para "Diseñar base de datos"
INSERT INTO tasks (title, description, parent_id, column_level, position_order) VALUES
('Analizar requisitos', 'Identificar entidades y relaciones', 4, 3, 1),
('Crear diagrama ER', 'Dibujar modelo entidad-relación', 4, 3, 2),
('Escribir SQL', 'Crear scripts de creación de tablas', 4, 3, 3);

-- Subtareas para "Aprender PHP avanzado"
INSERT INTO tasks (title, description, parent_id, column_level, position_order) VALUES
('Estudiar POO', 'Programación orientada a objetos', 2, 2, 1),
('Practicar patrones', 'Implementar design patterns', 2, 2, 2),
('Crear proyecto', 'Aplicar conocimientos adquiridos', 2, 2, 3);