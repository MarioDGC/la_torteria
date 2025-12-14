<?php
/**
 * Middleware de Autenticación
 * Verifica que el usuario tenga sesión activa
 */

class AuthMiddleware {

    /**
     * Verifica si hay sesión activa
     * Si no, redirige al login
     */
    public static function handle() {
        // Verificar si hay sesión activa
        if (!isset($_SESSION['user_id'])) {
            // Guardar URL destino para después del login
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];

            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        // Verificar expiración de sesión (inactividad)
        if (isset($_SESSION['last_activity'])) {
            $inactive = time() - $_SESSION['last_activity'];
            $sessionLifetime = ini_get('session.gc_maxlifetime');

            if ($inactive > $sessionLifetime) {
                session_unset();
                session_destroy();
                session_start();
                $_SESSION['login_timeout'] = true;
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
        }

        // Actualizar última actividad
        $_SESSION['last_activity'] = time();
    }
}