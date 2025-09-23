/**
 * Aplicación principal para gestión de micro-tareas
 */

class TaskManager {
    constructor() {
        this.selectedTask = null;
        this.selectedColumn = 0;
        this.currentTasks = [];
        this.columns = [];
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadTasks();
        this.loadStats();
    }

    setupEventListeners() {
        // Atajos de teclado
        document.addEventListener('keydown', (e) => this.handleKeyDown(e));
        
        // Búsqueda en tiempo real
        document.getElementById('searchInput').addEventListener('input', (e) => {
            if (e.target.value.length >= 2) {
                this.searchTasks(e.target.value);
            } else if (e.target.value.length === 0) {
                this.loadTasks();
            }
        });
    }

    handleKeyDown(e) {
        // Prevenir acciones cuando hay modales abiertos
        if (document.querySelector('.modal.show')) return;

        switch(e.key) {
            case 'ArrowUp':
                e.preventDefault();
                this.navigateUp();
                break;
            case 'ArrowDown':
                e.preventDefault();
                this.navigateDown();
                break;
            case 'ArrowLeft':
                e.preventDefault();
                this.navigateLeft();
                break;
            case 'ArrowRight':
                e.preventDefault();
                this.navigateRight();
                break;
            case 'Enter':
                e.preventDefault();
                if (this.selectedTask) {
                    this.editTask(this.selectedTask.id);
                }
                break;
            case ' ':
                e.preventDefault();
                if (this.selectedTask) {
                    this.toggleTaskComplete(this.selectedTask.id);
                }
                break;
            case 'Delete':
                e.preventDefault();
                if (this.selectedTask) {
                    this.deleteTask(this.selectedTask.id);
                }
                break;
        }

        // Atajos con modificadores
        if (e.ctrlKey) {
            switch(e.key) {
                case 'n':
                    e.preventDefault();
                    this.showCreateTaskModal();
                    break;
                case 'f':
                    e.preventDefault();
                    document.getElementById('searchInput').focus();
                    break;
                case 'p':
                    e.preventDefault();
                    this.printTasks();
                    break;
            }
        }
    }

    async loadTasks() {
        try {
            const response = await fetch('api.php?action=get_column_structure');
            const data = await response.json();
            
            if (data.success) {
                this.currentTasks = data.data;
                this.renderColumns();
            } else {
                this.showNotification('Error al cargar tareas: ' + data.message, 'error');
            }
        } catch (error) {
            this.showNotification('Error de conexión', 'error');
            console.error('Error loading tasks:', error);
        }
    }

    async loadStats() {
        try {
            const response = await fetch('api.php?action=get_stats');
            const data = await response.json();
            
            if (data.success) {
                const stats = data.data;
                document.getElementById('totalTasks').textContent = stats.total_tasks;
                document.getElementById('completedTasks').textContent = stats.completed_tasks;
                document.getElementById('pendingTasks').textContent = stats.pending_tasks;
                document.getElementById('maxDepth').textContent = stats.max_depth;
            }
        } catch (error) {
            console.error('Error loading stats:', error);
        }
    }

    renderColumns() {
        const container = document.getElementById('columnsContainer');
        container.innerHTML = '';
        this.columns = [];
        this.selectedTask = null;

        // Columna 1: Tareas principales
        this.renderColumn(this.currentTasks, 1, container);
    }

    renderColumn(tasks, level, container, parentTask = null) {
        // Evitar crear demasiadas columnas
        if (level > 5) return;
        
        const column = document.createElement('div');
        column.className = 'task-column';
        column.dataset.level = level;
        
        const header = document.createElement('div');
        header.className = 'column-header';
        header.innerHTML = `
            <h6 class="mb-2">
                ${level === 1 ? 'Tareas Principales' : `Subtareas de: ${parentTask ? this.escapeHtml(parentTask.title) : ''}`}
                <span class="badge bg-secondary ms-2">${tasks.length}</span>
            </h6>
            <button class="btn btn-sm btn-outline-primary" onclick="taskManager.showCreateTaskModal(${parentTask ? parentTask.id : null})">
                <i class="fas fa-plus"></i> Agregar
            </button>
        `;
        column.appendChild(header);

        const taskList = document.createElement('div');
        taskList.className = 'task-list';
        
        tasks.forEach(task => {
            const taskElement = this.createTaskElement(task, level);
            taskList.appendChild(taskElement);
        });

        column.appendChild(taskList);
        container.appendChild(column);
        this.columns.push(column);
    }

    createTaskElement(task, level = 1) {
        const element = document.createElement('div');
        element.className = `task-item ${task.is_completed ? 'completed' : ''} priority-${task.priority}`;
        element.dataset.taskId = task.id;
        element.dataset.hasChildren = task.has_children || 'false';
        
        element.innerHTML = `
            <div class="task-content">
                <div class="task-header">
                    <input type="checkbox" class="form-check-input me-2" 
                           ${task.is_completed ? 'checked' : ''} 
                           onchange="taskManager.toggleTaskComplete(${task.id})">
                    <span class="task-title">${this.escapeHtml(task.title)}</span>
                    ${task.has_children ? 
                        `<span class="badge bg-info ms-2"><i class="fas fa-chevron-right"></i></span>` : ''
                    }
                </div>
                ${task.description ? 
                    `<div class="task-description text-muted small mt-1">${this.escapeHtml(task.description)}</div>` : ''
                }
                <div class="task-actions mt-2">
                    <button class="btn btn-sm btn-outline-primary" onclick="taskManager.editTask(${task.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-success" onclick="taskManager.showCreateTaskModal(${task.id})">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="taskManager.deleteTask(${task.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;

        // Hacer seleccionable
        element.addEventListener('click', (e) => {
            e.stopPropagation();
            this.selectTask(task, element, level);
        });

        return element;
    }

    async selectTask(task, element, level) {
        // Remover selección anterior
        document.querySelectorAll('.task-item.selected').forEach(el => {
            el.classList.remove('selected');
        });

        // Seleccionar nueva tarea
        element.classList.add('selected');
        this.selectedTask = task;

        // Remover todas las columnas posteriores al nivel actual
        const container = document.getElementById('columnsContainer');
        const currentLevel = parseInt(element.closest('.task-column').dataset.level);
        
        const columnsToRemove = container.querySelectorAll(`.task-column[data-level]`);
        columnsToRemove.forEach(col => {
            const colLevel = parseInt(col.dataset.level);
            if (colLevel > currentLevel) {
                col.remove();
            }
        });

        // Si la tarea tiene hijos, cargar y mostrar la siguiente columna
        if (task.has_children || element.dataset.hasChildren === 'true') {
            try {
                const response = await fetch(`api.php?action=get_children&parent_id=${task.id}`);
                const data = await response.json();
                
                if (data.success && data.data.length > 0) {
                    this.renderColumn(data.data, currentLevel + 1, container, task);
                }
            } catch (error) {
                console.error('Error loading children:', error);
            }
        }
    }

    showCreateTaskModal(parentId = null) {
        document.getElementById('taskModalTitle').textContent = parentId ? 'Nueva Subtarea' : 'Nueva Tarea';
        document.getElementById('taskId').value = '';
        document.getElementById('parentId').value = parentId || '';
        document.getElementById('taskTitle').value = '';
        document.getElementById('taskDescription').value = '';
        document.getElementById('taskPriority').value = 'medium';
        
        const modal = new bootstrap.Modal(document.getElementById('taskModal'));
        modal.show();
        
        // Focus en el título
        setTimeout(() => {
            document.getElementById('taskTitle').focus();
        }, 150);
    }

    async editTask(taskId) {
        try {
            const response = await fetch(`api.php?action=get&id=${taskId}`);
            const data = await response.json();
            
            if (data.success) {
                const task = data.data;
                document.getElementById('taskModalTitle').textContent = 'Editar Tarea';
                document.getElementById('taskId').value = task.id;
                document.getElementById('parentId').value = task.parent_id || '';
                document.getElementById('taskTitle').value = task.title;
                document.getElementById('taskDescription').value = task.description || '';
                document.getElementById('taskPriority').value = task.priority;
                
                const modal = new bootstrap.Modal(document.getElementById('taskModal'));
                modal.show();
            }
        } catch (error) {
            this.showNotification('Error al cargar la tarea', 'error');
        }
    }

    async saveTask() {
        const taskId = document.getElementById('taskId').value;
        const parentId = document.getElementById('parentId').value;
        const title = document.getElementById('taskTitle').value.trim();
        const description = document.getElementById('taskDescription').value.trim();
        const priority = document.getElementById('taskPriority').value;

        if (!title) {
            this.showNotification('El título es requerido', 'error');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', taskId ? 'update' : 'create');
            if (taskId) formData.append('id', taskId);
            if (parentId) formData.append('parent_id', parentId);
            formData.append('title', title);
            formData.append('description', description);
            formData.append('priority', priority);

            const response = await fetch('api.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            if (data.success) {
                this.showNotification(data.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('taskModal')).hide();
                this.loadTasks();
                this.loadStats();
            } else {
                this.showNotification(data.message, 'error');
            }
        } catch (error) {
            this.showNotification('Error al guardar la tarea', 'error');
        }
    }

    async toggleTaskComplete(taskId) {
        try {
            const taskElement = document.querySelector(`[data-task-id="${taskId}"]`);
            const checkbox = taskElement.querySelector('input[type="checkbox"]');
            const isCompleted = checkbox.checked;

            const formData = new FormData();
            formData.append('action', 'toggle_complete');
            formData.append('id', taskId);
            formData.append('is_completed', isCompleted);

            const response = await fetch('api.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            if (data.success) {
                taskElement.classList.toggle('completed', isCompleted);
                this.loadStats();
            } else {
                checkbox.checked = !isCompleted; // Revertir cambio
                this.showNotification(data.message, 'error');
            }
        } catch (error) {
            this.showNotification('Error al actualizar la tarea', 'error');
        }
    }

    async deleteTask(taskId) {
        if (!confirm('¿Estás seguro de que deseas eliminar esta tarea y todas sus subtareas?')) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', taskId);

            const response = await fetch('api.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            if (data.success) {
                this.showNotification(data.message, 'success');
                this.loadTasks();
                this.loadStats();
            } else {
                this.showNotification(data.message, 'error');
            }
        } catch (error) {
            this.showNotification('Error al eliminar la tarea', 'error');
        }
    }

    async searchTasks(term = null) {
        const searchTerm = term || document.getElementById('searchInput').value.trim();
        
        if (!searchTerm) {
            this.loadTasks();
            return;
        }

        try {
            const response = await fetch(`api.php?action=search&term=${encodeURIComponent(searchTerm)}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderSearchResults(data.data);
            }
        } catch (error) {
            this.showNotification('Error en la búsqueda', 'error');
        }
    }

    renderSearchResults(tasks) {
        const container = document.getElementById('columnsContainer');
        container.innerHTML = '';

        const column = document.createElement('div');
        column.className = 'task-column';
        
        const header = document.createElement('div');
        header.className = 'column-header';
        header.innerHTML = `
            <h6 class="mb-2">
                Resultados de búsqueda
                <span class="badge bg-secondary ms-2">${tasks.length}</span>
            </h6>
            <button class="btn btn-sm btn-outline-secondary" onclick="taskManager.loadTasks()">
                <i class="fas fa-times"></i> Limpiar
            </button>
        `;
        column.appendChild(header);

        const taskList = document.createElement('div');
        taskList.className = 'task-list';
        
        tasks.forEach(task => {
            const taskElement = this.createTaskElement(task);
            taskList.appendChild(taskElement);
        });

        column.appendChild(taskList);
        container.appendChild(column);
    }

    showStats() {
        const statsRow = document.getElementById('statsRow');
        statsRow.style.display = statsRow.style.display === 'none' ? 'block' : 'none';
        this.loadStats();
    }

    printTasks() {
        window.print();
    }

    exportTasks() {
        // Implementación futura: exportar a JSON/CSV
        this.showNotification('Función de exportación en desarrollo', 'info');
    }

    showHelp() {
        const modal = new bootstrap.Modal(document.getElementById('helpModal'));
        modal.show();
    }

    showNotification(message, type = 'info') {
        const toast = document.getElementById('notificationToast');
        const toastBody = document.getElementById('toastMessage');
        
        toastBody.textContent = message;
        toast.className = `toast show text-bg-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'}`;
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
    }

    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, (m) => map[m]);
    }

    // Métodos de navegación por teclado
    navigateUp() {
        const selectedElement = document.querySelector('.task-item.selected');
        if (selectedElement) {
            const previousSibling = selectedElement.previousElementSibling;
            if (previousSibling && previousSibling.classList.contains('task-item')) {
                previousSibling.click();
            }
        }
    }

    navigateDown() {
        const selectedElement = document.querySelector('.task-item.selected');
        if (selectedElement) {
            const nextSibling = selectedElement.nextElementSibling;
            if (nextSibling && nextSibling.classList.contains('task-item')) {
                nextSibling.click();
            }
        } else {
            // Seleccionar primera tarea si no hay ninguna seleccionada
            const firstTask = document.querySelector('.task-item');
            if (firstTask) firstTask.click();
        }
    }

    navigateLeft() {
        // Implementación futura: navegar entre columnas
    }

    navigateRight() {
        // Implementación futura: navegar entre columnas
    }
}

// Funciones globales para compatibilidad
let taskManager;

function showCreateTaskModal(parentId = null) {
    taskManager.showCreateTaskModal(parentId);
}

function saveTask() {
    taskManager.saveTask();
}

function loadTasks() {
    taskManager.loadTasks();
}

function searchTasks() {
    taskManager.searchTasks();
}

function showStats() {
    taskManager.showStats();
}

function printTasks() {
    taskManager.printTasks();
}

function exportTasks() {
    taskManager.exportTasks();
}

function showHelp() {
    taskManager.showHelp();
}

// Inicializar aplicación
document.addEventListener('DOMContentLoaded', function() {
    taskManager = new TaskManager();
});