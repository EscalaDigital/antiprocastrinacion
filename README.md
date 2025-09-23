# Antiprocrastinación - Gestor de Micro-tareas

Una aplicación web en PHP inspirada en Colonnes que ayuda a superar la procrastinación mediante la gestión de micro-tareas organizadas en columnas dinámicas.

## Características principales

- **Columnas dinámicas**: Organiza tareas en múltiples niveles jerárquicos
- **Micro-tareas**: Descompone tareas complejas en pasos más pequeños y manejables
- **Gestión sin conexión**: Todos los datos se almacenan localmente (privacidad)
- **Interfaz rápida**: Navegación mediante atajos de teclado
- **Función de impresión**: Imprime tus micro-tareas en papel para mayor tangibilidad

## Estructura del proyecto

```
antiprocastrinacion/
├── config/          # Configuración de la aplicación
├── src/             # Código fuente PHP
├── public/          # Archivos públicos (index.php, etc.)
├── assets/          # CSS, JavaScript e imágenes
└── README.md        # Este archivo
```

## Instalación

1. Clona este proyecto en tu directorio htdocs de XAMPP
2. Configura la base de datos en `config/database.php`
3. Ejecuta el script SQL `config/schema.sql` en phpMyAdmin
4. Accede a `http://localhost/antiprocastrinacion/test.php` para verificar la configuración
5. Accede a `http://localhost/antiprocastrinacion/` para usar la aplicación

## Uso

- Crea una tarea principal
- Selecciona la tarea y crea subtareas en la siguiente columna
- Repite el proceso para crear tantos niveles como necesites
- Usa los atajos de teclado para navegación rápida

## Tecnologías

- PHP 7.4+
- MySQL/MariaDB
- HTML5, CSS3, JavaScript
- Bootstrap para UI responsiva