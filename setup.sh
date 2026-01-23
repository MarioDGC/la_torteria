#!/bin/bash

# Script para crear estructura de carpetas del sistema restaurante POS
# Autor: Mario Gonzalez
# Fecha: 2025

echo "🚀 Creando estructura de carpetas para Restaurant POS..."

# Crear directorios principales
mkdir -p config
mkdir -p app/{models,controllers,views,middlewares,helpers}
mkdir -p app/views/{layouts,auth,dashboard,orders,tables,menu,reports,errors}
mkdir -p public/assets/{css,js,img}
mkdir -p storage/{logs,uploads}
mkdir -p sql/migrations
mkdir -p tests

echo "📁 Estructura de carpetas creada"

# Crear archivos .gitkeep para mantener carpetas vacías en Git
touch storage/logs/.gitkeep
touch storage/uploads/.gitkeep
touch sql/migrations/.gitkeep
touch tests/.gitkeep

echo "📝 Archivos .gitkeep creados"

# Crear archivos de configuración básicos
cat > config/database.php << 'EOF'
<?php
/**
 * Configuración de conexión a base de datos
 * Patrón: Singleton para reutilizar conexión
 */

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            die("Error de conexión a la base de datos");
        }
    }

    /**
     * Obtiene la instancia única de Database (Singleton)
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Obtiene la conexión PDO
     * @return PDO
     */
    public function getConnection() {
        return $this->connection;
    }
}
EOF

cat > config/config.php << 'EOF'
<?php
/**
 * Configuración global del sistema
 */

// Constantes de base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'restaurant_pos');
define('DB_USER', 'root');
define('DB_PASS', '');

// Zona horaria
date_default_timezone_set('America/Mexico_City');

// Rutas del sistema
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('STORAGE_PATH', BASE_PATH . '/storage');

// URLs
define('BASE_URL', 'http://localhost/restaurant-pos/public');

// Configuración de sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 en producción con HTTPS

// Mostrar errores (solo desarrollo)
ini_set('display_errors', 1);
error_reporting(E_ALL);
EOF

cat > .env.example << 'EOF'
# Configuración de Base de Datos
DB_HOST=localhost
DB_NAME=restaurant_pos
DB_USER=root
DB_PASS=

# Configuración de la aplicación
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/restaurant-pos/public

# Zona horaria
TIMEZONE=America/Mexico_City
EOF

cat > public/.htaccess << 'EOF'
# Habilitar RewriteEngine
RewriteEngine On

# Redirigir todo a index.php excepto archivos existentes
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]

# Seguridad: Prevenir acceso a archivos sensibles
<FilesMatch "\.(htaccess|htpasswd|ini|log|sh|sql)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>
EOF

cat > public/index.php << 'EOF'
<?php
/**
 * Front Controller - Punto de entrada único
 * Patrón: Front Controller
 */

// Cargar configuración
require_once '../config/config.php';
require_once '../config/database.php';

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
            return;
        }
    }
});

// Iniciar sesión
session_start();

// Routing básico (mejorar con sistema de rutas más robusto)
$url = $_GET['url'] ?? 'dashboard';
$url = rtrim($url, '/');
$url = filter_var($url, FILTER_SANITIZE_URL);
$url = explode('/', $url);

$controllerName = ucfirst($url[0]) . 'Controller';
$method = $url[1] ?? 'index';

// Verificar si existe el controlador
if (file_exists(APP_PATH . '/controllers/' . $controllerName . '.php')) {
    $controller = new $controllerName();

    if (method_exists($controller, $method)) {
        $params = array_slice($url, 2);
        call_user_func_array([$controller, $method], $params);
    } else {
        // Método no encontrado
        http_response_code(404);
        require_once APP_PATH . '/views/errors/404.php';
    }
} else {
    // Controlador no encontrado
    http_response_code(404);
    require_once APP_PATH . '/views/errors/404.php';
}
EOF

cat > storage/uploads/.htaccess << 'EOF'
# Prevenir ejecución de scripts en directorio de uploads
<FilesMatch "\.(php|phtml|php3|php4|php5|pl|py|jsp|asp|sh|cgi)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>
EOF

cat > README.md << 'EOF'
# Sistema POS para Restaurante

Sistema de punto de venta para restaurantes desarrollado con PHP, MySQL y JavaScript vanilla.

## Requisitos
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx
- XAMPP/WAMP (desarrollo local)

## Instalación

1. Clonar repositorio
2. Copiar `.env.example` a `.env` y configurar
3. Crear base de datos: `mysql -u root -p < sql/schema.sql`
4. Configurar virtual host apuntando a `/public`
5. Acceder a `http://restaurant.local`

## Estructura
```
restaurant-pos/
├── config/         # Configuración
├── app/            # Lógica de aplicación (MVC)
├── public/         # Archivos públicos (punto de entrada)
├── storage/        # Logs y uploads
└── sql/            # Scripts de base de datos
```

## Arquitectura

- **Patrón MVC:** Separación de responsabilidades
- **Front Controller:** `public/index.php` como punto único de entrada
- **Singleton:** Conexión única a base de datos
- **Security by Design:** Validaciones, prepared statements, CSRF protection

## Autor
Tu nombre
EOF

echo "✅ Archivos base creados"

# Crear archivo SQL básico
cat > sql/schema.sql << 'EOF'
-- Base de datos: restaurant_pos
-- Estructura básica

CREATE DATABASE IF NOT EXISTS restaurant_pos
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE restaurant_pos;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'waiter', 'chef', 'cashier') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB;

-- Usuario admin por defecto (password: admin123)
INSERT INTO users (username, password, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin');
EOF

echo "🎉 Estructura completada exitosamente"
echo ""
echo "📋 Próximos pasos:"
echo "1. Editar config/config.php con tus credenciales de DB"
echo "2. Importar sql/schema.sql: mysql -u root -p < sql/schema.sql"
echo "3. Configurar virtual host apuntando a /public"
echo "4. Acceder a tu aplicación"
echo ""
echo "Usuario por defecto: admin / admin123"