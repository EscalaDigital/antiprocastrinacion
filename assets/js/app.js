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
    this.setupDropdownPortals();
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
                <span class="chip chip-count ms-2"><i class="fas fa-list-check"></i> ${tasks.length}</span>
            </h6>
            <button class="btn btn-sm btn-outline-primary" onclick="taskManager.showCreateTaskModal(${parentTask ? parentTask.id : null})">
                <i class="fas fa-plus"></i> Agregar
            </button>
        `;
        column.appendChild(header);

        const taskList = document.createElement('div');
        taskList.className = 'task-list';
        // Soporte drop en la lista para mover a este contenedor (raíz o hijo actual)
        taskList.dataset.dropLevel = level;
        if (parentTask && parentTask.id) {
            taskList.dataset.dropParentId = String(parentTask.id);
        } else {
            taskList.dataset.dropParentId = '';
        }
        this.attachListDropHandlers(taskList);
        
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
        element.draggable = true;
        
        element.innerHTML = `
            <div class="task-content">
                <div class="task-header align-items-center">
                    <input type="checkbox" class="form-check-input me-2" 
                           ${task.is_completed ? 'checked' : ''} 
                           onchange="taskManager.toggleTaskComplete(${task.id})">
                    <span class="task-chips me-2"></span>
                    <span class="task-title">${this.escapeHtml(task.title)}</span>
                    ${task.has_children ? `<span class="badge bg-info ms-2"><i class="fas fa-chevron-right"></i></span>` : ''}
                    <div class="ms-auto dropdown">
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown" title="Más acciones">
                            <i class="fas fa-ellipsis"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" onclick="taskManager.editTask(${task.id})"><i class="fas fa-edit me-2"></i>Editar</a></li>
                            <li><a class="dropdown-item" href="#" onclick="taskManager.createCalendarEvent(${task.id})"><i class="far fa-calendar-plus me-2"></i>Crear en Calendar</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li class="dropdown-header">Impresión</li>
                            <li><a class="dropdown-item" href="#" onclick="taskManager.printTaskOnly(${task.id})"><i class="fas fa-file-alt me-2"></i>Solo esta tarea</a></li>
                            <li><a class="dropdown-item" href="#" onclick="taskManager.printTaskPDF(${task.id})"><i class="fas fa-file-pdf me-2"></i>Generar PDF</a></li>
                            <li><a class="dropdown-item" href="#" onclick="taskManager.printTask(${task.id})"><i class="fas fa-sitemap me-2"></i>Con subtareas</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#" onclick="taskManager.deleteTask(${task.id})"><i class="fas fa-trash me-2"></i>Eliminar</a></li>
                        </ul>
                    </div>
                </div>
                ${task.description ? `
                <div class="task-description text-muted mt-1 desc-clamp" id="desc-${task.id}">${this.escapeHtml(task.description)}</div>
                <div class="desc-toggle" onclick="taskManager.toggleDescription(${task.id})">Ver más</div>
                ` : ''}
                <div class="task-actions mt-2">
                    <button class="btn btn-sm btn-outline-success" onclick="taskManager.showCreateTaskModal(${task.id})">
                        <i class="fas fa-plus me-1"></i> Subtarea
                    </button>
                </div>
            </div>
        `;

        // Insertar chip de Gmail si aplica
        try {
            const chips = element.querySelector('.task-chips');
            let gmailHref = null;
            if (task.gmail_thread_id) {
                gmailHref = `https://mail.google.com/mail/u/0/#all/${task.gmail_thread_id}`;
            } else if (task.gmail_message_id) {
                gmailHref = `https://mail.google.com/mail/u/0/#all/${task.gmail_message_id}`;
            } else if (task.gmail_url) {
                gmailHref = task.gmail_url;
            } else if (task.description && task.description.indexOf('https://mail.google.com/') !== -1) {
                const start = task.description.indexOf('https://mail.google.com/');
                const rest = task.description.slice(start);
                const spaceIndex = rest.search(/\s/);
                gmailHref = spaceIndex === -1 ? rest : rest.slice(0, spaceIndex);
            }
            if (gmailHref && chips) {
                const a = document.createElement('a');
                a.className = 'chip chip-gmail';
                a.href = gmailHref;
                a.target = '_blank';
                a.title = 'Abrir en Gmail';
                a.innerHTML = '<i class="fas fa-envelope"></i>';
                chips.appendChild(a);
            }
        } catch (e) {}

        // Hacer seleccionable
        element.addEventListener('click', (e) => {
            e.stopPropagation();
            this.selectTask(task, element, level);
        });

        // Drag & drop handlers
        element.addEventListener('dragstart', (ev) => {
            ev.stopPropagation();
            ev.dataTransfer.setData('text/plain', String(task.id));
            ev.dataTransfer.effectAllowed = 'move';
            element.classList.add('dragging');
        });
        element.addEventListener('dragend', () => {
            element.classList.remove('dragging');
            document.querySelectorAll('.drop-target').forEach(el => el.classList.remove('drop-target'));
        });
        // Permitir soltar sobre otra tarea: before/after/into según zona
        element.addEventListener('dragover', (ev) => {
            ev.preventDefault();
            ev.dataTransfer.dropEffect = 'move';
            const rect = element.getBoundingClientRect();
            const offsetY = ev.clientY - rect.top;
            const zoneTop = rect.height * 0.25;
            const zoneBottom = rect.height * 0.75;
            element.classList.add('drop-target');
            element.dataset.dropZone = offsetY < zoneTop ? 'before' : (offsetY > zoneBottom ? 'after' : 'into');
        });
        element.addEventListener('dragleave', () => {
            element.classList.remove('drop-target');
            delete element.dataset.dropZone;
        });
        element.addEventListener('drop', async (ev) => {
            ev.preventDefault();
            ev.stopPropagation();
            const placement = element.dataset.dropZone || 'into';
            element.classList.remove('drop-target');
            delete element.dataset.dropZone;
            const draggedId = parseInt(ev.dataTransfer.getData('text/plain'), 10);
            if (!draggedId || draggedId === task.id) return;
            if (placement === 'into') {
                await this.moveTask(draggedId, task.id);
            } else {
                await this.moveRelative(draggedId, task.id, placement);
            }
        });

        return element;
    }

    attachListDropHandlers(listEl) {
        listEl.addEventListener('dragover', (ev) => {
            ev.preventDefault();
            ev.dataTransfer.dropEffect = 'move';
            listEl.classList.add('drop-target');
        });
        listEl.addEventListener('dragleave', () => {
            listEl.classList.remove('drop-target');
        });
        listEl.addEventListener('drop', async (ev) => {
            ev.preventDefault();
            listEl.classList.remove('drop-target');
            const draggedId = parseInt(ev.dataTransfer.getData('text/plain'), 10);
            if (!draggedId) return;
            const parentIdAttr = listEl.dataset.dropParentId || '';
            const newParentId = parentIdAttr === '' ? null : parseInt(parentIdAttr, 10);
            await this.moveTask(draggedId, newParentId);
        });
    }

    async moveTask(taskId, newParentId) {
        try {
            const formData = new FormData();
            formData.append('action', 'update_parent');
            formData.append('id', String(taskId));
            if (newParentId === null || typeof newParentId === 'undefined') {
                formData.append('parent_id', '');
            } else {
                formData.append('parent_id', String(newParentId));
            }
            const response = await fetch('api.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.success) {
                this.showNotification('Tarea movida', 'success');
                // Recargar columnas a partir de la raíz para simplificar
                await this.loadTasks();
                await this.loadStats();
            } else {
                this.showNotification(data.message || 'No se pudo mover la tarea', 'error');
            }
        } catch (e) {
            this.showNotification('Error moviendo la tarea', 'error');
        }
    }

    async moveRelative(id, targetId, placement) {
        try {
            const formData = new FormData();
            formData.append('action', 'move_relative');
            formData.append('id', String(id));
            formData.append('target_id', String(targetId));
            formData.append('placement', placement);
            const response = await fetch('api.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.success) {
                this.showNotification('Tarea reordenada', 'success');
                await this.loadTasks();
                await this.loadStats();
            } else {
                this.showNotification(data.message || 'No se pudo reordenar', 'error');
            }
        } catch (e) {
            this.showNotification('Error reordenando', 'error');
        }
    }

    async selectTask(task, element, level) {
        // Remover selección anterior
        document.querySelectorAll('.task-item.selected').forEach(el => {
            el.classList.remove('selected');
        });

        // Seleccionar nueva tarea
        element.classList.add('selected');
        this.selectedTask = task;
        // nada

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

    toggleDescription(taskId){
        const el = document.getElementById(`desc-${taskId}`);
        if(!el) return;
        const toggle = el.nextElementSibling;
        const expanded = el.classList.toggle('desc-expanded');
        if(expanded){
            el.classList.remove('desc-clamp');
            if(toggle) toggle.textContent = 'Ver menos';
        } else {
            el.classList.add('desc-clamp');
            if(toggle) toggle.textContent = 'Ver más';
        }
    }

    setupDropdownPortals() {
        // Colocar los dropdowns en body para evitar clipping
        document.addEventListener('shown.bs.dropdown', (e) => {
            const toggle = e.target; // button
            const menu = toggle.nextElementSibling;
            if (!menu || !menu.classList.contains('dropdown-menu')) return;
            const placeholder = document.createElement('div');
            placeholder.style.display = 'none';
            menu.parentNode.insertBefore(placeholder, menu);
            document.body.appendChild(menu);
            menu.classList.add('dropdown-portal');
            const rect = toggle.getBoundingClientRect();
            const top = rect.bottom + window.scrollY;
            const left = rect.left + window.scrollX;
            menu.style.top = `${top}px`;
            menu.style.left = `${left}px`;
            menu.dataset.portal = '1';
            menu._placeholder = placeholder;
        });

        document.addEventListener('hide.bs.dropdown', (e) => {
            const toggle = e.target;
            const menu = toggle.nextElementSibling;
            if (!menu || menu.dataset.portal !== '1') return;
            menu.classList.remove('dropdown-portal');
            menu.style.top = '';
            menu.style.left = '';
            if (menu._placeholder) {
                menu._placeholder.parentNode.insertBefore(menu, menu._placeholder);
                menu._placeholder.remove();
                delete menu._placeholder;
            }
            delete menu.dataset.portal;
        });
    }

    showCreateTaskModal(parentId = null) {
        document.getElementById('taskModalTitle').textContent = parentId ? 'Nueva Subtarea' : 'Nueva Tarea';
        document.getElementById('taskId').value = '';
        document.getElementById('parentId').value = parentId || '';
        document.getElementById('taskTitle').value = '';
        document.getElementById('taskDescription').value = '';
    document.getElementById('taskGmailUrl').value = '';
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
                    document.getElementById('taskGmailUrl').value = task.gmail_url || (task.gmail_thread_id ? `https://mail.google.com/mail/u/0/#all/${task.gmail_thread_id}` : '');
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
    const gmailUrl = document.getElementById('taskGmailUrl').value.trim();
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
            if (gmailUrl) formData.append('gmail_url', gmailUrl);
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

    async createGoogleTask(taskId) {
        try {
            const formData = new FormData();
            formData.append('action', 'create_google_task');
            formData.append('id', taskId);
            const response = await fetch('api.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.success) {
                this.showNotification('Creada en Google Tasks', 'success');
            } else {
                this.showNotification(data.message || 'Error creando en Google Tasks', 'error');
            }
        } catch (e) {
            this.showNotification('Error creando en Google Tasks', 'error');
        }
    }

    async createCalendarEvent(taskId) {
        // Abrir modal con valores por defecto
        document.getElementById('calendarTaskId').value = taskId;
        const today = new Date();
        const y = today.getFullYear();
        const m = String(today.getMonth() + 1).padStart(2, '0');
        const d = String(today.getDate()).padStart(2, '0');
        document.getElementById('calDate').value = `${y}-${m}-${d}`;
        document.getElementById('calStartTime').value = '09:00';
        document.getElementById('calEndTime').value = '10:00';
        document.getElementById('calReminder').value = 30;
        document.getElementById('calLocation').value = '';
        document.getElementById('calNotes').value = '';
        document.getElementById('calAllDay').checked = false;
        new bootstrap.Modal(document.getElementById('calendarModal')).show();
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
                <span class="chip chip-count ms-2"><i class="fas fa-list-check"></i> ${tasks.length}</span>
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

    async applyFilters() {
        try {
            const prios = ['high','medium','low'].filter(p => document.getElementById(`f-prio-${p}`)?.checked);
            const statusSel = document.getElementById('f-status');
            const gmailSel = document.getElementById('f-gmail');
            const levelMin = document.getElementById('f-level-min').value;
            const levelMax = document.getElementById('f-level-max').value;
            const orderSel = document.getElementById('f-order');

            const formData = new FormData();
            formData.append('action', 'filter');
            if (prios.length) formData.append('priorities', prios.join(','));
            if (statusSel) formData.append('status', statusSel.value);
            if (gmailSel && gmailSel.value !== 'any') formData.append('has_gmail', gmailSel.value);
            if (levelMin) formData.append('level_min', levelMin);
            if (levelMax) formData.append('level_max', levelMax);
            if (orderSel) formData.append('order', orderSel.value);

            const response = await fetch('api.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.success) {
                this.renderFilteredResults(data.data);
            } else {
                this.showNotification(data.message || 'Error aplicando filtros', 'error');
            }
        } catch (e) {
            this.showNotification('Error aplicando filtros', 'error');
        }
    }

    clearFilters() {
        ['f-prio-high','f-prio-medium','f-prio-low'].forEach(id => { const el = document.getElementById(id); if (el) el.checked = true; });
        const s = (id, val) => { const el = document.getElementById(id); if (el) el.value = val; };
        s('f-status','all'); s('f-gmail','any'); s('f-level-min',''); s('f-level-max',''); s('f-order','priority');
        this.loadTasks();
    }

    async quickUrgentFilter() {
        try {
            const formData = new FormData();
            formData.append('action', 'filter');
            formData.append('priorities', 'high');
            formData.append('status', 'pending');
            formData.append('order', 'priority');
            const response = await fetch('api.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.success) {
                this.renderFilteredResults(data.data, { title: 'Urgentes (Alta prioridad, pendientes)' });
            }
        } catch (e) {
            this.showNotification('Error filtrando urgentes', 'error');
        }
    }

    renderFilteredResults(tasks, opts = {}) {
        const container = document.getElementById('columnsContainer');
        container.innerHTML = '';
        const column = document.createElement('div');
        column.className = 'task-column';
        const title = opts.title || 'Resultados (filtros)';
        const header = document.createElement('div');
        header.className = 'column-header';
        header.innerHTML = `
            <h6 class="mb-2">
                ${title}
                <span class="chip chip-count ms-2"><i class="fas fa-list-check"></i> ${tasks.length}</span>
            </h6>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary" onclick="taskManager.loadTasks()"><i class="fas fa-times"></i> Cerrar</button>
            </div>`;
        column.appendChild(header);
        const taskList = document.createElement('div');
        taskList.className = 'task-list';
        tasks.forEach(task => taskList.appendChild(this.createTaskElement(task)));
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

    async printTask(taskId) {
        try {
            const response = await fetch(`api.php?action=get_task_tree&id=${taskId}`);
            const data = await response.json();
            
            if (data.success) {
                this.openPrintWindow(data.data);
            } else {
                this.showNotification('Error al cargar la tarea para imprimir', 'error');
            }
        } catch (error) {
            this.showNotification('Error al imprimir la tarea', 'error');
        }
    }

    async printTaskOnly(taskId) {
        try {
            const response = await fetch(`api.php?action=get&id=${taskId}`);
            const data = await response.json();
            
            if (data.success) {
                this.openPrintWindowSingle(data.data);
            } else {
                this.showNotification('Error al cargar la tarea para imprimir', 'error');
            }
        } catch (error) {
            this.showNotification('Error al imprimir la tarea', 'error');
        }
    }

    async printTaskPDF(taskId) {
        try {
            const response = await fetch(`api.php?action=get&id=${taskId}`);
            const data = await response.json();
            
            if (data.success) {
                this.generateTaskPDF(data.data);
            } else {
                this.showNotification('Error al cargar la tarea para generar PDF', 'error');
            }
        } catch (error) {
            this.showNotification('Error al generar PDF', 'error');
        }
    }

    openPrintWindow(taskData) {
        const printWindow = window.open('', '_blank', 'width=800,height=600');
        const printContent = this.generatePrintContent(taskData);
        
        printWindow.document.write(printContent);
        printWindow.document.close();
        
        // Dar tiempo para que se carguen los estilos y luego imprimir
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 500);
    }

    openPrintWindowSingle(taskData) {
        const printWindow = window.open('', '_blank', 'width=600,height=400');
        const printContent = this.generateSingleTaskPrintContent(taskData);
        
        printWindow.document.write(printContent);
        printWindow.document.close();
        
        // Dar tiempo para que se carguen los estilos y luego imprimir
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 500);
    }

    generateSingleTaskPrintContent(task) {
        const currentDate = new Date().toLocaleDateString('es-ES');
        const currentTime = new Date().toLocaleTimeString('es-ES');
        
        return `
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Tarea: ${this.escapeHtml(task.title)}</title>
            <style>
                body {
                    font-family: 'Segoe UI', Arial, sans-serif;
                    margin: 30px;
                    color: #333;
                    line-height: 1.6;
                }
                .header {
                    text-align: center;
                    border-bottom: 3px solid #0d6efd;
                    padding-bottom: 15px;
                    margin-bottom: 30px;
                }
                .app-title {
                    font-size: 1.8em;
                    color: #0d6efd;
                    margin: 0;
                }
                .print-info {
                    color: #666;
                    font-size: 0.9em;
                    margin-top: 5px;
                }
                .task-card {
                    background: #f8f9fa;
                    border: 2px solid #e9ecef;
                    border-radius: 10px;
                    padding: 25px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                .task-status {
                    display: inline-block;
                    padding: 5px 15px;
                    border-radius: 20px;
                    font-weight: bold;
                    margin-bottom: 15px;
                    ${task.is_completed ? 
                        'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' :
                        'background: #fff3cd; color: #856404; border: 1px solid #ffeaa7;'
                    }
                }
                .task-title {
                    font-size: 1.6em;
                    font-weight: bold;
                    color: #2c3e50;
                    margin: 15px 0;
                    ${task.is_completed ? 'text-decoration: line-through; opacity: 0.7;' : ''}
                }
                .task-description {
                    background: white;
                    padding: 15px;
                    border-radius: 5px;
                    color: #555;
                    margin: 15px 0;
                    border-left: 4px solid #0d6efd;
                }
                .task-meta {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 15px;
                    margin-top: 20px;
                }
                .meta-item {
                    background: white;
                    padding: 10px;
                    border-radius: 5px;
                    border: 1px solid #dee2e6;
                }
                .meta-label {
                    font-weight: bold;
                    color: #495057;
                    font-size: 0.9em;
                }
                .meta-value {
                    color: #6c757d;
                    margin-top: 3px;
                }
                .priority-indicator {
                    display: inline-block;
                    width: 12px;
                    height: 12px;
                    border-radius: 50%;
                    margin-right: 8px;
                    ${this.getPriorityColor(task.priority)}
                }
                .footer {
                    margin-top: 40px;
                    padding-top: 20px;
                    border-top: 1px solid #dee2e6;
                    text-align: center;
                    color: #6c757d;
                    font-size: 0.8em;
                }
                @media print {
                    body { margin: 15px; }
                    .task-card { box-shadow: none; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1 class="app-title">📋 Antiprocrastinación</h1>
                <div class="print-info">Tarea individual impresa el ${currentDate} a las ${currentTime}</div>
            </div>
            
            <div class="task-card">
                <div class="task-status">
                    ${task.is_completed ? '✅ COMPLETADA' : '⏳ PENDIENTE'}
                </div>
                
                <h2 class="task-title">${this.escapeHtml(task.title)}</h2>
                
                ${task.description ? `
                <div class="task-description">
                    <strong>Descripción:</strong><br>
                    ${this.escapeHtml(task.description)}
                </div>
                ` : ''}
                
                <div class="task-meta">
                    <div class="meta-item">
                        <div class="meta-label">🎯 Prioridad</div>
                        <div class="meta-value">
                            <span class="priority-indicator"></span>
                            ${this.getPriorityText(task.priority)}
                        </div>
                    </div>
                    
                    <div class="meta-item">
                        <div class="meta-label">📊 Estado</div>
                        <div class="meta-value">
                            ${task.is_completed ? 'Completada' : 'Pendiente'}
                        </div>
                    </div>
                    
                    <div class="meta-item">
                        <div class="meta-label">📅 Creada</div>
                        <div class="meta-value">
                            ${new Date(task.created_at).toLocaleDateString('es-ES')}
                        </div>
                    </div>
                    
                    <div class="meta-item">
                        <div class="meta-label">🔄 Actualizada</div>
                        <div class="meta-value">
                            ${new Date(task.updated_at).toLocaleDateString('es-ES')}
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="footer">
                Generado por Antiprocrastinación - Gestor de Micro-tareas<br>
                <em>Esta es una tarea individual sin subtareas</em>
            </div>
        </body>
        </html>
        `;
    }

    generateTaskPDF(task) {
        // Verificar si jsPDF está disponible
        if (typeof window.jsPDF === 'undefined') {
            // Cargar jsPDF dinámicamente
            this.loadJsPDF().then(() => {
                this.createPDF(task);
            }).catch(() => {
                this.showNotification('No se pudo cargar la librería PDF. Usando impresión normal...', 'warning');
                this.printTaskOnly(task.id);
            });
        } else {
            this.createPDF(task);
        }
    }

    async loadJsPDF() {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    createPDF(task) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        // Configuración
        const pageWidth = doc.internal.pageSize.getWidth();
        const margin = 20;
        const lineHeight = 7;
        let yPosition = margin;
        
        // Función para añadir texto con wrap
        const addText = (text, x, y, maxWidth, fontSize = 12) => {
            doc.setFontSize(fontSize);
            const lines = doc.splitTextToSize(text, maxWidth);
            doc.text(lines, x, y);
            return y + (lines.length * lineHeight);
        };
        
        // Header
        doc.setFontSize(20);
        doc.setTextColor(13, 110, 253);
        doc.text('📋 Antiprocrastinación', margin, yPosition);
        yPosition += 10;
        
        doc.setFontSize(10);
        doc.setTextColor(100, 100, 100);
        const currentDate = new Date().toLocaleDateString('es-ES');
        const currentTime = new Date().toLocaleTimeString('es-ES');
        doc.text(`Tarea individual generada el ${currentDate} a las ${currentTime}`, margin, yPosition);
        yPosition += 15;
        
        // Línea separadora
        doc.setDrawColor(13, 110, 253);
        doc.setLineWidth(1);
        doc.line(margin, yPosition, pageWidth - margin, yPosition);
        yPosition += 15;
        
        // Estado de la tarea
        doc.setFontSize(12);
        if (task.is_completed) {
            doc.setTextColor(21, 87, 36);
            doc.text('✅ COMPLETADA', margin, yPosition);
        } else {
            doc.setTextColor(133, 100, 4);
            doc.text('⏳ PENDIENTE', margin, yPosition);
        }
        yPosition += 15;
        
        // Título de la tarea
        doc.setFontSize(16);
        doc.setTextColor(44, 62, 80);
        yPosition = addText(task.title, margin, yPosition, pageWidth - 2 * margin, 16);
        yPosition += 10;
        
        // Descripción
        if (task.description) {
            doc.setFontSize(12);
            doc.setTextColor(80, 80, 80);
            doc.text('Descripción:', margin, yPosition);
            yPosition += 8;
            
            doc.setTextColor(100, 100, 100);
            yPosition = addText(task.description, margin, yPosition, pageWidth - 2 * margin);
            yPosition += 10;
        }
        
        // Metadatos
        doc.setFontSize(12);
        doc.setTextColor(80, 80, 80);
        doc.text('Información de la tarea:', margin, yPosition);
        yPosition += 10;
        
        doc.setFontSize(10);
        doc.setTextColor(100, 100, 100);
        
        const metaData = [
            `🎯 Prioridad: ${this.getPriorityText(task.priority)}`,
            `📊 Estado: ${task.is_completed ? 'Completada' : 'Pendiente'}`,
            `📅 Creada: ${new Date(task.created_at).toLocaleDateString('es-ES')}`,
            `🔄 Actualizada: ${new Date(task.updated_at).toLocaleDateString('es-ES')}`,
            `📍 Nivel: ${task.column_level}`
        ];
        
        metaData.forEach(item => {
            doc.text(item, margin, yPosition);
            yPosition += 6;
        });
        
        // Footer
        yPosition = doc.internal.pageSize.getHeight() - 20;
        doc.setFontSize(8);
        doc.setTextColor(150, 150, 150);
        doc.text('Generado por Antiprocrastinación - Gestor de Micro-tareas', margin, yPosition);
        doc.text('Esta es una tarea individual sin subtareas', margin, yPosition + 5);
        
        // Guardar PDF
        const fileName = `tarea-${task.id}-${task.title.substring(0, 30).replace(/[^a-zA-Z0-9]/g, '-')}.pdf`;
        doc.save(fileName);
        
        this.showNotification('PDF generado exitosamente', 'success');
    }

    getPriorityColor(priority) {
        switch(priority) {
            case 'high': return 'background: #dc3545;';
            case 'medium': return 'background: #ffc107;';
            case 'low': return 'background: #198754;';
            default: return 'background: #6c757d;';
        }
    }

    generatePrintContent(task) {
        const currentDate = new Date().toLocaleDateString('es-ES');
        
        let html = `
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Tarea: ${this.escapeHtml(task.title)}</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                    color: #333;
                }
                .header {
                    border-bottom: 2px solid #0d6efd;
                    padding-bottom: 10px;
                    margin-bottom: 20px;
                }
                .task-main {
                    background: #f8f9fa;
                    padding: 15px;
                    border-radius: 5px;
                    margin-bottom: 20px;
                }
                .task-title {
                    font-size: 1.5em;
                    font-weight: bold;
                    color: #0d6efd;
                    margin-bottom: 10px;
                }
                .task-description {
                    color: #666;
                    margin-bottom: 10px;
                }
                .task-meta {
                    font-size: 0.9em;
                    color: #888;
                }
                .subtasks {
                    margin-top: 20px;
                }
                .subtask {
                    background: white;
                    border: 1px solid #ddd;
                    padding: 10px;
                    margin-bottom: 10px;
                    border-radius: 3px;
                }
                .subtask-title {
                    font-weight: bold;
                    margin-bottom: 5px;
                }
                .subtask-description {
                    color: #666;
                    font-size: 0.9em;
                }
                .priority-high { border-left: 4px solid #dc3545; }
                .priority-medium { border-left: 4px solid #ffc107; }
                .priority-low { border-left: 4px solid #198754; }
                .completed {
                    opacity: 0.7;
                    text-decoration: line-through;
                }
                .checkbox {
                    margin-right: 10px;
                }
                @media print {
                    body { margin: 0; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>📋 Antiprocrastinación</h1>
                <p>Impreso el: ${currentDate}</p>
            </div>
            
            <div class="task-main priority-${task.priority} ${task.is_completed ? 'completed' : ''}">
                <div class="task-title">
                    ${task.is_completed ? '☑' : '☐'} ${this.escapeHtml(task.title)}
                </div>
                ${task.description ? `<div class="task-description">${this.escapeHtml(task.description)}</div>` : ''}
                <div class="task-meta">
                    Prioridad: ${this.getPriorityText(task.priority)} | 
                    Estado: ${task.is_completed ? 'Completada' : 'Pendiente'} |
                    Nivel: ${task.column_level}
                </div>
            </div>
        `;
        
        if (task.children && task.children.length > 0) {
            html += `
            <div class="subtasks">
                <h2>Subtareas (${task.children.length})</h2>
                ${this.generateSubtasksHTML(task.children)}
            </div>
            `;
        }
        
        html += `
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #888; font-size: 0.8em;">
                Generado por Antiprocrastinación - Gestor de Micro-tareas
            </div>
        </body>
        </html>
        `;
        
        return html;
    }

    generateSubtasksHTML(tasks) {
        let html = '';
        
        tasks.forEach(task => {
            html += `
            <div class="subtask priority-${task.priority} ${task.is_completed ? 'completed' : ''}">
                <div class="subtask-title">
                    <span class="checkbox">${task.is_completed ? '☑' : '☐'}</span>
                    ${this.escapeHtml(task.title)}
                </div>
                ${task.description ? `<div class="subtask-description">${this.escapeHtml(task.description)}</div>` : ''}
                <div style="font-size: 0.8em; color: #888; margin-top: 5px;">
                    Prioridad: ${this.getPriorityText(task.priority)} | Estado: ${task.is_completed ? 'Completada' : 'Pendiente'}
                </div>
            </div>
            `;
            
            if (task.children && task.children.length > 0) {
                html += `<div style="margin-left: 20px;">${this.generateSubtasksHTML(task.children)}</div>`;
            }
        });
        
        return html;
    }

    getPriorityText(priority) {
        switch(priority) {
            case 'high': return 'Alta';
            case 'medium': return 'Media';
            case 'low': return 'Baja';
            default: return 'Media';
        }
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

    async createTaskFromGmailPrompt(parentId = null) {
        const input = prompt('Pega la URL de Gmail o el ID del hilo/mensaje:');
        if (!input) return;
        try {
            const formData = new FormData();
            formData.append('action', 'create_from_gmail');
            formData.append('gmail', input);
            if (parentId) formData.append('parent_id', parentId);
            const response = await fetch('api.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.success) {
                this.showNotification('Tarea creada desde Gmail', 'success');
                this.loadTasks();
            } else {
                this.showNotification(data.message || 'No se pudo crear desde Gmail', 'error');
            }
        } catch (e) {
            this.showNotification('Error conectando con Gmail', 'error');
        }
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

function printTask(taskId) {
    taskManager.printTask(taskId);
}

function printTaskOnly(taskId) {
    taskManager.printTaskOnly(taskId);
}

function printTaskPDF(taskId) {
    taskManager.printTaskPDF(taskId);
}

function exportTasks() {
    taskManager.exportTasks();
}

function showHelp() {
    taskManager.showHelp();
}

// Submit del modal de Calendar
async function submitCalendarEvent() {
    const taskId = document.getElementById('calendarTaskId').value;
    const date = document.getElementById('calDate').value;
    const startTime = document.getElementById('calStartTime').value;
    const endTime = document.getElementById('calEndTime').value;
    const reminder = document.getElementById('calReminder').value;
    const location = document.getElementById('calLocation').value.trim();
    const notes = document.getElementById('calNotes').value.trim();
    const allDay = document.getElementById('calAllDay').checked;

    if (!date) { taskManager.showNotification('Selecciona una fecha', 'error'); return; }
    if (!allDay && (!startTime || !endTime)) { taskManager.showNotification('Indica hora de inicio y fin', 'error'); return; }

    // Construir ISO simple (sin zona) para backend; el backend aplicará TZ
    let startIso, endIso;
    if (allDay) {
        startIso = `${date}T00:00:00`;
        endIso = `${date}T23:59:00`;
    } else {
        startIso = `${date}T${startTime}:00`;
        endIso = `${date}T${endTime}:00`;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'create_calendar_event');
        formData.append('id', taskId);
        formData.append('start', startIso);
        formData.append('end', endIso);
        if (reminder !== '') formData.append('reminder_minutes', reminder);
        if (location) formData.append('location', location);
        if (notes) formData.append('notes', notes);
        if (allDay) formData.append('all_day', '1');
        const response = await fetch('api.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) {
            taskManager.showNotification('Evento creado en Calendar', 'success');
            bootstrap.Modal.getInstance(document.getElementById('calendarModal')).hide();
        } else {
            taskManager.showNotification(data.message || 'Error creando evento', 'error');
        }
    } catch (e) {
        taskManager.showNotification('Error creando evento', 'error');
    }
}

// Inicializar aplicación
document.addEventListener('DOMContentLoaded', function() {
    // Aplicar tema guardado
    try {
        const savedTheme = localStorage.getItem('ap_theme');
        if (savedTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.removeAttribute('data-theme');
        }
    } catch (e) {}
    taskManager = new TaskManager();
});

// Alternar tema (claro/oscuro) y persistir
function toggleTheme(){
    const html = document.documentElement;
    const isDark = html.getAttribute('data-theme') === 'dark';
    if (isDark){
        html.removeAttribute('data-theme');
        try { localStorage.setItem('ap_theme', 'light'); } catch(e){}
    } else {
        html.setAttribute('data-theme', 'dark');
        try { localStorage.setItem('ap_theme', 'dark'); } catch(e){}
    }
}