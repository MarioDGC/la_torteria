<?php
/**
 * Auth Controller
 * Maneja autenticación y autorización del sistema
 */

class AuthController {

    /**
     * Mostrar formulario de login
     */
    public function login() {
        // Si ya hay sesión activa, redirigir al dashboard
        if (isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    
        // Obtener errores FLASH y eliminarlos inmediatamente
        $validationErrors = $_SESSION['validation_errors'] ?? [];
        $lastUsername = $_SESSION['login_attempt']['username'] ?? '';
        $timeout = $_SESSION['login_timeout'] ?? false;
        $credentialsError = $_SESSION['credentials_error'] ?? false;
        
        // CRÍTICO: Limpiar TODO después de leer
        unset($_SESSION['validation_errors']);
        unset($_SESSION['login_attempt']);
        unset($_SESSION['login_timeout']);
        unset($_SESSION['credentials_error']);
        
        $data = [
            'pageTitle' => 'Iniciar Sesión',
            'validationErrors' => $validationErrors,
            'lastUsername' => $lastUsername,
            'timeout' => $timeout,
            'credentialsError' => $credentialsError,  // ✅ Faltaba esta línea
            'customCSS' => ['pages/login'],
            'customJS' => ['login']
        ];
    
        // Usar sistema de layouts
        $this->renderAuthView('auth/login-form', $data);
    }

    /**
     * Procesar autenticación
     */
    public function authenticate() {
        // ⚠️ DEBUG TEMPORAL
        error_log("=== DEBUG LOGIN ===");
        error_log("Username: " . ($_POST['username'] ?? 'vacío'));
        error_log("Password length: " . strlen($_POST['password'] ?? ''));
        error_log("===================");
        // Solo permitir método POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        // Obtener y sanitizar datos
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // Guardar intento para repoblar el formulario
        $_SESSION['login_attempt'] = [
            'username' => $username,
            'timestamp' => time()
        ];

        // Array para acumular errores
        $errors = [];

        // Validar username
        if (empty($username)) {
            $errors[] = 'El usuario es requerido';
        } elseif (strlen($username) < 3) {
            $errors[] = 'El usuario debe tener al menos 3 caracteres';
        } elseif (strlen($username) > 50) {
            $errors[] = 'El usuario no puede exceder 50 caracteres';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'El usuario solo puede contener letras, números y guión bajo';
        }

        // Validar password
        if (empty($password)) {
            $errors[] = 'La contraseña es requerida';
        } elseif (strlen($password) < 3) {
            $errors[] = 'La contraseña debe tener al menos 3 caracteres';
        }

        // Si hay errores de validación, redirigir con mensaje
        if (!empty($errors)) {
            $_SESSION['validation_errors'] = $errors;
            // NO pasar error en query string, usar sesión
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        // Delegar autenticación al modelo
        $userModel = new User();
        $user = $userModel->validateCredentials($username, $password);

        if ($user) {

            // Limpiar errores
            unset($_SESSION['validation_errors']);
            unset($_SESSION['login_attempt']);

            // Regenerar ID de sesión (Security by Design)
            session_regenerate_id(true);

            // Establecer variables de sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role_name'];
            $_SESSION['user_role'] = $user['role_name'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['last_activity'] = time();
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

            // Registrar último login
            $userModel->updateLastLogin($user['id']);

            // Log de inicio de sesión exitoso
            error_log("✅ Login exitoso: {$username} desde {$_SERVER['REMOTE_ADDR']}");

            // ✅ Limpiar datos temporales
            unset($_SESSION['login_attempt']);
            unset($_SESSION['validation_errors']);
            unset($_SESSION['login_attempts']);

            // Verificar si hay URL de redirección guardada
            unset($_SESSION['redirect_after_login']);

            // Redirección directa al dashboard
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        } else {
            // Credenciales incorrectas
            error_log("❌ Login fallido: {$username} desde {$_SERVER['REMOTE_ADDR']}");

            $_SESSION['credentials_error'] = true;
            // Rate limiting
            $this->incrementLoginAttempts($username);

            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    /**
     * Incrementa contador de intentos fallidos
     * Patrón: Rate Limiting básico
     * 
     * @param string $username
     */
    private function incrementLoginAttempts($username) {
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = [];
        }

        $key = md5($username . $_SERVER['REMOTE_ADDR']);
        
        if (!isset($_SESSION['login_attempts'][$key])) {
            $_SESSION['login_attempts'][$key] = [
                'count' => 0,
                'first_attempt' => time()
            ];
        }

        $_SESSION['login_attempts'][$key]['count']++;
        $_SESSION['login_attempts'][$key]['last_attempt'] = time();

        // Si excede 5 intentos en 15 minutos, bloquear temporalmente
        if ($_SESSION['login_attempts'][$key]['count'] >= 5) {
            $timeSinceFirst = time() - $_SESSION['login_attempts'][$key]['first_attempt'];
            
            if ($timeSinceFirst < 900) { // 15 minutos
                $_SESSION['account_locked_until'] = time() + 300; // Bloquear por 5 minutos
                error_log("🔒 Cuenta bloqueada temporalmente: {$username} desde {$_SERVER['REMOTE_ADDR']}");
            } else {
                // Reset si pasó el tiempo
                unset($_SESSION['login_attempts'][$key]);
            }
        }
    }

    /**
     * Cerrar sesión
     */
    public function logout() {
        // Log de cierre de sesión
        if (isset($_SESSION['username'])) {
            error_log("Logout: {$_SESSION['username']} desde {$_SERVER['REMOTE_ADDR']}");
        }

        // Destruir todas las variables de sesión
        $_SESSION = array();

        // Destruir la cookie de sesión
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        // Destruir la sesión
        session_destroy();

        // Redirigir al login
        header('Location: ' . BASE_URL . '/login');
        exit;
    }

    /**
     * Renderiza una vista usando el layout de autenticación
     * 
     * @param string $view Ruta de la vista (relativa a /app/views/)
     * @param array $data Datos a pasar a la vista
     */
    private function renderAuthView($view, $data = []) {
        // Extraer variables del array $data
        extract($data);

        // Capturar output de la vista parcial
        ob_start();
        $viewFile = APP_PATH . '/views/' . $view . '.php';
        
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            die("Vista no encontrada: {$viewFile}");
        }
        
        $content = ob_get_clean();

        // Cargar layout con el contenido
        require_once APP_PATH . '/views/layouts/auth.php';
    }

    /**
     * Verificar si el usuario está autenticado
     *
     * @return bool
     */
    public static function isAuthenticated() {
        return isset($_SESSION['user_id']);
    }

    /**
     * Obtener datos del usuario actual
     *
     * @return array|null
     */
    public static function getCurrentUser() {
        if (!self::isAuthenticated()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'name' => $_SESSION['user_name'] ?? null,
            'role' => $_SESSION['role'] ?? null,
        ];
    }
}