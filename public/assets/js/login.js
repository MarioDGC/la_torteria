/**
 * Login Form Validation
 * 
 * @author Mario
 * @version 2.1.0
 */

(function() {
    'use strict';

    // ========================================
    // Variables Globales
    // ========================================
    let loginForm = null;
    let usernameInput = null;
    let passwordInput = null;
    let submitButton = null;

    /**
     * Inicializa referencias a elementos del DOM
     */
    function initFormElements() {
        loginForm = document.querySelector('form[action*="authenticate"]');
        usernameInput = document.getElementById('username');
        passwordInput = document.getElementById('password');
        submitButton = loginForm?.querySelector('button[type="submit"]');
    }

    /**
     * Inicializa la validación del formulario
     */
    function initFormValidation() {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Remover alertas previas
            removeAllAlerts();
            
            // Validar campos
            const validationResult = validateForm();
            
            if (!validationResult.isValid) {
                showValidationError(validationResult.errors);
                focusFirstError(validationResult.errors);
                return false;
            }
            
            // Si pasa validación, enviar formulario
            submitForm();
        });
    }

    /**
     * Inicializa cuando el DOM está listo
     */
    document.addEventListener('DOMContentLoaded', function() {
        cleanUrlParameters();
        initFormElements();
        
        if (!loginForm) {
            console.warn('⚠️ Formulario de login no encontrado');
            return;
        }

        initFormValidation();
        initRealTimeValidation();
        
        console.log('✅ Validación de login inicializada');
    });

    /**
     * Validación en tiempo real
     */
    function initRealTimeValidation() {
        // Validar al perder foco
        usernameInput.addEventListener('blur', function() {
            validateField(this, 'username');
        });

        passwordInput.addEventListener('blur', function() {
            validateField(this, 'password');
        });

        // Remover error al escribir
        usernameInput.addEventListener('input', function() {
            removeFieldError(this);
        });

        passwordInput.addEventListener('input', function() {
            removeFieldError(this);
        });
    }

    /**
     * Valida todo el formulario
     * @returns {Object} {isValid: boolean, errors: Array}
     */
    function validateForm() {
        const errors = [];

        // Validar username
        const username = usernameInput.value.trim();
        if (!username) {
            errors.push({
                field: 'username',
                message: 'El usuario es requerido'
            });
        } else if (username.length < 3) {
            errors.push({
                field: 'username',
                message: 'El usuario debe tener al menos 3 caracteres'
            });
        }

        // Validar password
        const password = passwordInput.value.trim();
        if (!password) {
            errors.push({
                field: 'password',
                message: 'La contraseña es requerida'
            });
        } else if (password.length < 3) {
            errors.push({
                field: 'password',
                message: 'La contraseña debe tener al menos 3 caracteres'
            });
        }

        return {
            isValid: errors.length === 0,
            errors: errors
        };
    }

    /**
     * Valida un campo individual
     * @param {HTMLInputElement} field
     * @param {string} fieldName
     */
    function validateField(field, fieldName) {
        const value = field.value.trim();
        
        // Siempre remover error primero
        removeFieldError(field);

        if (!value) {
            addFieldError(field, 'Este campo es requerido');
            return false;
        }

        if (value.length < 3) {
            addFieldError(field, 'Debe tener al menos 3 caracteres');
            return false;
        }

        return true;
    }

    /**
     * Agrega error visual a un campo
     * @param {HTMLInputElement} field
     * @param {string} message
     */
    function addFieldError(field, message) {
        // Agregar clase de error
        field.classList.add('input-error');
        
        // Crear o actualizar mensaje de error
        let errorMsg = field.parentElement.nextElementSibling;
        
        if (!errorMsg || !errorMsg.classList.contains('field-error')) {
            errorMsg = document.createElement('div');
            errorMsg.className = 'field-error';
            field.parentElement.insertAdjacentElement('afterend', errorMsg);
        }
        
        errorMsg.innerHTML = `<i class="fas fa-exclamation-circle me-1"></i>${message}`;
    }

    /**
     * Remueve error visual de un campo
     * @param {HTMLInputElement} field
     */
    function removeFieldError(field) {
        field.classList.remove('input-error');
        
        const errorMsg = field.parentElement.nextElementSibling;
        if (errorMsg && errorMsg.classList.contains('field-error')) {
            errorMsg.remove();
        }
    }

    /**
     * Muestra mensaje de error general
     * @param {Array} errors
     */
    function showValidationError(errors) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show mt-3';
        alertDiv.setAttribute('role', 'alert');
        
        let errorList = '<i class="fas fa-exclamation-circle me-2"></i><strong>Corrige los siguientes errores:</strong><ul class="mb-0 mt-2">';
        
        errors.forEach(error => {
            errorList += `<li>${error.message}</li>`;
            
            // Marcar campo con error
            const field = document.getElementById(error.field);
            if (field) {
                addFieldError(field, error.message);
            }
        });
        
        errorList += '</ul>';
        
        // ✅ Agregar botón de cerrar manual
        errorList += '<button type="button" class="btn-close" aria-label="Cerrar"></button>';
        
        alertDiv.innerHTML = errorList;
        
        // Insertar después del formulario
        loginForm.insertAdjacentElement('afterend', alertDiv);
        
        // ✅ Event listener para cerrar manual
        const closeBtn = alertDiv.querySelector('.btn-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                dismissAlert(alertDiv);
            });
        }
        
        // ✅ Auto-cerrar después de 8 segundos
        setTimeout(() => {
            dismissAlert(alertDiv);
        }, 8000);
    }

    /**
     * Cierra una alerta con animación
     * @param {HTMLElement} alertElement
     */
    function dismissAlert(alertElement) {
        if (!alertElement || !alertElement.parentElement) return;
        
        // Remover clase 'show' para activar fade out
        alertElement.classList.remove('show');
        
        // Esperar a que termine la transición antes de remover del DOM
        setTimeout(() => {
            if (alertElement.parentElement) {
                alertElement.remove();
            }
        }, 300); // Debe coincidir con la duración de la transición CSS
    }

    /**
     * Enfoca el primer campo con error
     * @param {Array} errors
     */
    function focusFirstError(errors) {
        if (errors.length > 0) {
            const firstErrorField = document.getElementById(errors[0].field);
            if (firstErrorField) {
                firstErrorField.focus();
                firstErrorField.select();
            }
        }
    }

    /**
     * Remueve todas las alertas
     */
    function removeAllAlerts() {
        // Remover alertas generales
        document.querySelectorAll('.alert').forEach(alert => {
            if (!alert.classList.contains('alert-warning')) { // Mantener timeout warning
                alert.remove();
            }
        });
        
        // Remover errores de campos
        document.querySelectorAll('.input-error').forEach(field => {
            removeFieldError(field);
        });
    }

    /**
     * Envía el formulario con loading state
     */
    function submitForm() {
        if (submitButton) {
            submitButton.disabled = true;
            const originalText = submitButton.innerHTML;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Iniciando sesión...';
            
            // Timeout de seguridad por si falla la redirección
            setTimeout(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            }, 5000);
        }

        loginForm.submit();
    }

    /**
     * Limpia parámetros de la URL
     */
    function cleanUrlParameters() {
        if (window.location.search) {
            const url = window.location.protocol + "//" +
                        window.location.host +
                        window.location.pathname;
            window.history.replaceState({}, document.title, url);
        }
    }


})();