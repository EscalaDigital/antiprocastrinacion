# Instrucciones de instalación

## Requisitos previos
- XAMPP instalado y funcionando
- PHP 7.4 o superior
- MySQL/MariaDB
- Navegador web moderno

## Instalación paso a paso

### 1. Clonar o descargar el proyecto
Coloca el proyecto en tu directorio `htdocs` de XAMPP:
```
c:\xampp\htdocs\antiprocastrinacion\
```

### 2. Iniciar servicios de XAMPP
- Abre el panel de control de XAMPP
- Inicia los servicios **Apache** y **MySQL**

### 3. Configurar la base de datos

#### Opción A: Usando phpMyAdmin
1. Abre tu navegador y ve a: `http://localhost/phpmyadmin`
2. Crea una nueva base de datos llamada `antiprocrastinacion`
3. Selecciona la base de datos creada
4. Ve a la pestaña "Importar"
5. Selecciona el archivo `config/schema.sql`
6. Haz clic en "Continuar"

#### Opción B: Desde línea de comandos
```bash
# Navega al directorio de MySQL
cd C:\xampp\mysql\bin

# Conecta a MySQL
mysql -u root -p

# Crea la base de datos
CREATE DATABASE antiprocrastinacion CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Usar la base de datos
USE antiprocrastinacion;

# Importar el esquema
source C:\xampp\htdocs\antiprocastrinacion\config\schema.sql;
```

### 4. Verificar configuración
Revisa el archivo `config/database.php` y asegúrate de que los datos de conexión sean correctos:
- Host: localhost
- Base de datos: antiprocrastinacion
- Usuario: root
- Contraseña: (vacía por defecto)

### 5. Acceder a la aplicación
Primero verifica que todo esté configurado correctamente visitando: `http://localhost/antiprocastrinacion/test.php`

Si todo está bien, accede a la aplicación en: `http://localhost/antiprocastrinacion/`

## Uso de la aplicación

### Características principales
- **Columnas dinámicas**: Las tareas se organizan en columnas que representan diferentes niveles de jerarquía
- **Micro-tareas**: Descompone tareas grandes en pequeñas acciones manejables
- **Atajos de teclado**: Navegación rápida sin usar el mouse
- **Impresión**: Imprime tus tareas para tenerlas físicamente en tu escritorio
- **Búsqueda**: Encuentra rápidamente cualquier tarea
- **Estadísticas**: Ve tu progreso de un vistazo

### Flujo de trabajo recomendado
1. **Crea una tarea principal** - Por ejemplo: "Completar proyecto web"
2. **Descompón en subtareas** - Selecciona la tarea y crea subtareas específicas
3. **Crea micro-tareas** - Divide las subtareas en acciones muy pequeñas (5-15 minutos cada una)
4. **Ejecuta las micro-tareas** - Completa una a la vez y márcalas como terminadas
5. **Imprime si es necesario** - Usa Ctrl+P para imprimir tus tareas actuales

### Atajos de teclado
- `↑/↓` - Navegar entre tareas
- `←/→` - Cambiar entre columnas
- `Enter` - Editar tarea seleccionada
- `Espacio` - Marcar tarea como completada/pendiente
- `Ctrl+N` - Nueva tarea
- `Ctrl+F` - Buscar
- `Ctrl+P` - Imprimir
- `Delete` - Eliminar tarea

## Solución de problemas

### Error de conexión a la base de datos
- Verifica que MySQL esté ejecutándose en XAMPP
- Comprueba que la base de datos `antiprocrastinacion` exista
- Revisa los datos de conexión en `config/database.php`

### La página no carga
- Asegúrate de que Apache esté ejecutándose
- Verifica que la URL sea correcta: `http://localhost/antiprocastrinacion/public/`
- Revisa los logs de error de Apache en XAMPP

### Las tareas no se guardan
- Verifica que todas las tablas se hayan creado correctamente
- Comprueba que el usuario tenga permisos de escritura en la base de datos
- Revisa la consola del navegador para errores JavaScript

## Personalización

### Cambiar la configuración de la base de datos
Edita el archivo `config/database.php` para cambiar los datos de conexión.

### Modificar estilos
Los estilos CSS están en `assets/css/style.css`. Puedes personalizarlos según tus preferencias.

### Agregar nuevas características
- Modifica `src/Task.php` para nuevas funcionalidades del modelo
- Actualiza `public/api.php` para nuevos endpoints
- Extiende `assets/js/app.js` para nuevas características del frontend

## Contribución

Este proyecto está inspirado en [Colonnes](https://www.colonnes.com/) y está diseñado como una alternativa web open source para la gestión de micro-tareas.

Si encuentras errores o quieres agregar nuevas características, siéntete libre de:
1. Reportar issues
2. Crear pull requests
3. Sugerir mejoras

## Licencia

Este proyecto es de código abierto y está disponible para uso personal y educativo.