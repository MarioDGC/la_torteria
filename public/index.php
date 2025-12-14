<?php
/**
 * Front Controller - Punto de entrada único
 * Patrón: Front Controller
 * Versión: 2.0 - Mejorado con manejo robusto de errores
 */

// Cargar configuración
require_once '../config/config.php';
require_once '../config/database.php';

// Verificar que BASE_URL esté definida
if (!defined('BASE_URL')) {
    die('Error: BASE_URL no está configurada en config.php');
}

// Autoloader simple (o usa Composer en producción)
spl_autoload_register(function ($class) {
    $paths = [
        APP_PATH . '/models/',
        APP_PATH . '/controllers/',
        APP_PATH . '/middlewares/',
        APP_PATH . '/helpers/',
    ];

    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }

    // Log de clases no encontradas (útil para debugging)
    error_log("Autoloader: Clase no encontrada - {$class}");
    return false;
});

// Iniciar sesión
session_start();

// ========================================
// ROUTING
// ========================================

// Obtener URL solicitada
$url = $_GET['url'] ?? 'dashboard';
$url = rtrim($url, '/');
$url = filter_var($url, FILTER_SANITIZE_URL);
$url = explode('/', $url);

// Extraer controlador y método
$controllerName = ucfirst(strtolower($url[0])) . 'Controller';
$method = $url[1] ?? 'index';

// Rutas públicas (no requieren autenticación)
$publicRoutes = ['login', 'auth'];

// ========================================
// ALIAS DE RUTAS
// ========================================

// /login → AuthController::login()
if ($url[0] === '' || $url[0] === 'dashboard') {
    $controllerName = 'DashboardController';
    $method = 'index';
}

// Caso especial: login → AuthController
if ($url[0] === 'login') {
    $controllerName = 'AuthController';
    $method = 'login';
}

// Caso especial: auth con submétodos
if ($url[0] === 'auth') {
    $controllerName = 'AuthController';
    $method = $url[1] ?? 'index';
}

// ========================================
// VERIFICACIÓN DE AUTENTICACIÓN
// ========================================

// Si no es ruta pública y no hay sesión, redirigir a login
if (!in_array($url[0], $publicRoutes) && !isset($_SESSION['user_id'])) {
    // Guardar URL destino para redirigir después del login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];

    header('Location: ' . BASE_URL . '/login');
    exit;
}

// ========================================
// EJECUCIÓN DEL CONTROLADOR
// ========================================

$controllerFile = APP_PATH . '/controllers/' . $controllerName . '.php';

if (file_exists($controllerFile)) {
    // Cargar controlador
    require_once $controllerFile;

    // Verificar que la clase exista (case-sensitivity)
    if (!class_exists($controllerName)) {
        error_log("Controlador existe como archivo pero la clase no: {$controllerName}");
        show404();
    }

    $controller = new $controllerName();

    // Verificar que el método exista
    if (method_exists($controller, $method)) {
        // Ejecutar método con parámetros adicionales
        $params = array_slice($url, 2);
        call_user_func_array([$controller, $method], $params);
    } else {
        // Método no encontrado
        error_log("Método no encontrado: {$controllerName}::{$method}");
        show404();
    }
} else {
    // Controlador no encontrado
    error_log("Controlador no encontrado: {$controllerFile}");
    show404();
}

// Temporal en index.php para debug:
// try {
//     $db = Database::getInstance()->getConnection();
//     echo "✅ Conexión exitosa a BD<br>";
// } catch (Exception $e) {
//     echo "❌ Error: " . $e->getMessage();
// }

// ========================================
// FUNCIONES AUXILIARES
// ========================================

/**
 * Muestra página de error 404
 * Con fallback si el archivo no existe
 */
function show404() {
    http_response_code(404);

    $error404File = APP_PATH . '/views/errors/404.php';

    if (file_exists($error404File)) {
        require_once $error404File;
    } else {
        // Fallback: Error 404 simple si no existe el archivo
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>404 - Página no encontrada</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    color: white;
                    text-align: center;
                    padding: 20px;
                }
                .error-container {
                    background: rgba(255,255,255,0.1);
                    backdrop-filter: blur(10px);
                    padding: 3rem;
                    border-radius: 20px;
                    max-width: 500px;
                }
                h1 {
                    font-size: 6rem;
                    margin-bottom: 1rem;
                    text-shadow: 0 4px 8px rgba(0,0,0,0.3);
                }
                p {
                    font-size: 1.2rem;
                    margin-bottom: 2rem;
                    opacity: 0.9;
                }
                a {
                    display: inline-block;
                    background: white;
                    color: #667eea;
                    padding: 1rem 2rem;
                    border-radius: 50px;
                    text-decoration: none;
                    font-weight: 600;
                    transition: transform 0.3s ease;
                }
                a:hover {
                    transform: translateY(-3px);
                    box-shadow: 0 6px 20px rgba(0,0,0,0.3);
                }
            </style>
        </head>
        <body>
            <div class="error-container">
                <h1>404</h1>
                <p>Página no encontrada</p>
                <a href="<?php echo BASE_URL; ?>">Volver al inicio</a>
            </div>
        </body>
        </html>
        <?php
    }
    exit;
}

/**
 * Muestra página de error 500
 */
function show500($message = 'Error interno del servidor') {
    http_response_code(500);

    $error500File = APP_PATH . '/views/errors/500.php';

    if (file_exists($error500File)) {
        require_once $error500File;
    } else {
        echo "<h1>500 - Error del Servidor</h1>";
        echo "<p>" . htmlspecialchars($message) . "</p>";
    }
    exit;
}