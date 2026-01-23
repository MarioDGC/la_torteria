/**
 * Toast Notification System
 * Sistema de notificaciones moderno y reutilizable
 *
 * Uso:
 * Toast.success('Operación exitosa');
 * Toast.error('Ocurrió un error');
 * Toast.warning('Advertencia');
 * Toast.info('Información');
 * Toast.show('Mensaje personalizado', 'primary', 5000);
 */

class ToastNotification {
    constructor() {
        this.container = null;
        this.defaultDuration = 4000;
        this.positions = {
            'top-right': 'top-0 end-0',
            'top-left': 'top-0 start-0',
            'bottom-right': 'bottom-0 end-0',
            'bottom-left': 'bottom-0 start-0',
            'top-center': 'top-0 start-50 translate-middle-x',
            'bottom-center': 'bottom-0 start-50 translate-middle-x'
        };
        this.currentPosition = 'top-right';
        this.init();
    }

    /**
     * Inicializa el contenedor de toasts
     */
    init() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.className = `toast-container position-fixed p-3 ${this.positions[this.currentPosition]}`;
            this.container.style.zIndex = '9999';
            document.body.appendChild(this.container);
        }
    }

    /**
     * Muestra un toast
     * @param {string} message - Mensaje a mostrar
     * @param {string} type - Tipo: success, error, warning, info, primary
     * @param {number} duration - Duración en ms (0 = no se cierra automáticamente)
     * @param {string} title - Título opcional
     */
    show(message, type = 'info', duration = null, title = null) {
        const toastElement = this.createToast(message, type, title);
        this.container.appendChild(toastElement);

        // Inicializar toast de Bootstrap
        const bsToast = new bootstrap.Toast(toastElement, {
            autohide: duration !== 0,
            delay: duration || this.defaultDuration
        });

        // Mostrar
        bsToast.show();

        // Eliminar del DOM después de ocultarse
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });

        return bsToast;
    }

    /**
     * Crea el elemento HTML del toast
     * @private
     */
    createToast(message, type, title) {
        const toastId = `toast-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        const config = this.getTypeConfig(type);

        const toast = document.createElement('div');
        toast.className = `toast align-items-center border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        toast.id = toastId;

        // Título dinámico o predeterminado
        const toastTitle = title || config.title;

        toast.innerHTML = `
            <div class="toast-header ${config.headerClass}">
                <i class="${config.icon} me-2"></i>
                <strong class="me-auto">${toastTitle}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body ${config.bodyClass}">
                ${message}
            </div>
        `;

        return toast;
    }

    /**
     * Configuración de estilos por tipo
     * @private
     */
    getTypeConfig(type) {
        const configs = {
            success: {
                icon: 'fas fa-check-circle',
                title: 'Éxito',
                headerClass: 'bg-success text-white',
                bodyClass: 'bg-light text-dark'
            },
            error: {
                icon: 'fas fa-exclamation-circle',
                title: 'Error',
                headerClass: 'bg-danger text-white',
                bodyClass: 'bg-light text-dark'
            },
            warning: {
                icon: 'fas fa-exclamation-triangle',
                title: 'Advertencia',
                headerClass: 'bg-warning text-dark',
                bodyClass: 'bg-light text-dark'
            },
            info: {
                icon: 'fas fa-info-circle',
                title: 'Información',
                headerClass: 'bg-info text-white',
                bodyClass: 'bg-light text-dark'
            },
            primary: {
                icon: 'fas fa-bell',
                title: 'Notificación',
                headerClass: 'bg-primary text-white',
                bodyClass: 'bg-light text-dark'
            }
        };

        return configs[type] || configs.info;
    }

    /**
     * Shortcuts para tipos comunes
     */
    success(message, duration = null, title = null) {
        return this.show(message, 'success', duration, title);
    }

    error(message, duration = null, title = null) {
        return this.show(message, 'error', duration, title);
    }

    warning(message, duration = null, title = null) {
        return this.show(message, 'warning', duration, title);
    }

    info(message, duration = null, title = null) {
        return this.show(message, 'info', duration, title);
    }

    /**
     * Cambia la posición de los toasts
     * @param {string} position - Posición: top-right, top-left, etc.
     */
    setPosition(position) {
        if (this.positions[position]) {
            this.currentPosition = position;
            this.container.className = `toast-container position-fixed p-3 ${this.positions[position]}`;
            this.container.style.zIndex = '9999';
        }
    }

    /**
     * Toast con confirmación de acción
     * @param {string} message - Mensaje
     * @param {Function} onConfirm - Callback al confirmar
     * @param {Function} onCancel - Callback al cancelar
     */
    confirm(message, onConfirm, onCancel = null) {
        const toastElement = document.createElement('div');
        toastElement.className = 'toast align-items-center border-0';
        toastElement.setAttribute('role', 'alert');

        toastElement.innerHTML = `
            <div class="toast-header bg-warning text-dark">
                <i class="fas fa-question-circle me-2"></i>
                <strong class="me-auto">Confirmación</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body bg-light">
                <p class="mb-2">${message}</p>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-success confirm-btn">
                        <i class="fas fa-check"></i> Confirmar
                    </button>
                    <button class="btn btn-sm btn-secondary cancel-btn" data-bs-dismiss="toast">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </div>
        `;

        this.container.appendChild(toastElement);

        const bsToast = new bootstrap.Toast(toastElement, { autohide: false });
        bsToast.show();

        // Event listeners
        toastElement.querySelector('.confirm-btn').addEventListener('click', () => {
            bsToast.hide();
            if (onConfirm) onConfirm();
        });

        toastElement.querySelector('.cancel-btn').addEventListener('click', () => {
            if (onCancel) onCancel();
        });

        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }
}

// Instancia global
const Toast = new ToastNotification();

// Exportar para uso con módulos ES6 (opcional)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Toast;
}