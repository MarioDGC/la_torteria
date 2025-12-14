<?php
/**
 * Tables Controller
 * Manages restaurant table operations
 * 
 * Design Pattern: MVC Controller
 * Security: AuthMiddleware required for all methods
 */

class TablesController {

    /**
     * Main tables view
     * Shows grid of tables with current status
     */
    public function index() {
        AuthMiddleware::handle();

        try {
            // Get tables and stats from model
            $tableModel = new Table();
            $tables = $tableModel->getAllTables();
            $stats = $tableModel->getTableStats();

            // Get waiters for assignment modal
            $userModel = new User();
            $waiters = $userModel->getUsersByRole('waiter');

            // Prepare data for view
            $data = [
                'pageTitle' => 'Gestión de Mesas',
                'currentView' => 'tables',
                'tables' => $tables,
                'stats' => $stats,
                'waiters' => $waiters,
                'userRole' => $_SESSION['role'] ?? 'guest',
                'customCSS' => [],  // tables.css is already in main.css
                'customJS' => ['tables']
            ];

            // Render view with dashboard layout
            ob_start();
            include APP_PATH . '/views/tables/tables-index.php';
            $content = ob_get_clean();

            $this->render('layouts/dashboard', array_merge($data, ['content' => $content]));

        } catch (Exception $e) {
            error_log("Error in TablesController::index - " . $e->getMessage());
            $this->show500("Error al cargar las mesas");
        }
    }

    /**
     * Get current status of all tables (AJAX)
     * Returns JSON for real-time updates
     */
    public function getTablesStatus() {
        header('Content-Type: application/json');

        try {
            $tableModel = new Table();
            $tables = $tableModel->getAllTables();
            $stats = $tableModel->getTableStats();

            echo json_encode([
                'success' => true,
                'tables' => $tables,
                'stats' => $stats
            ]);
        } catch (Exception $e) {
            error_log("Error in getTablesStatus: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener estado de mesas'
            ]);
        }
        exit;
    }

    /**
     * Change table status
     * POST: table_id, new_status
     */
    public function changeStatus() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        try {
            $tableId = filter_input(INPUT_POST, 'table_id', FILTER_VALIDATE_INT);
            $newStatus = filter_input(INPUT_POST, 'new_status', FILTER_SANITIZE_STRING);

            if (!$tableId || !$newStatus) {
                throw new Exception('Datos inválidos');
            }

            // Validate allowed statuses
            $allowedStatuses = ['available', 'occupied', 'reserved', 'maintenance'];
            if (!in_array($newStatus, $allowedStatuses)) {
                throw new Exception('Estado no válido');
            }

            $tableModel = new Table();
            
            // Security: Only allow certain transitions
            $currentTable = $tableModel->getTableById($tableId);
            
            if (!$currentTable) {
                throw new Exception('Mesa no encontrada');
            }

            // Can't set to occupied manually (must assign waiter)
            if ($newStatus === 'occupied') {
                throw new Exception('Use la opción de asignar mesero para ocupar una mesa');
            }

            $result = $tableModel->updateStatus($tableId, $newStatus);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Estado actualizado correctamente'
                ]);
            } else {
                throw new Exception('Error al actualizar estado');
            }

        } catch (Exception $e) {
            error_log("Error in changeStatus: " . $e->getMessage());
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Assign waiter to table
     * POST: table_id, waiter_id
     */
    public function assignWaiter() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        try {
            $tableId = filter_input(INPUT_POST, 'table_id', FILTER_VALIDATE_INT);
            $waiterId = filter_input(INPUT_POST, 'waiter_id', FILTER_VALIDATE_INT);

            if (!$tableId || !$waiterId) {
                throw new Exception('Datos inválidos');
            }

            // Verify table is available or reserved
            $tableModel = new Table();
            $table = $tableModel->getTableById($tableId);
            
            if (!$table) {
                throw new Exception('Mesa no encontrada');
            }

            if ($table['status'] === 'maintenance') {
                throw new Exception('La mesa está en mantenimiento');
            }

            if ($table['status'] === 'occupied') {
                throw new Exception('La mesa ya está ocupada');
            }

            // Create or get account
            $accountModel = new Account();
            $accountId = $accountModel->createOrGetAccount($tableId, $waiterId);

            if ($accountId) {
                // Update table status to occupied
                $tableModel->updateStatus($tableId, 'occupied');

                echo json_encode([
                    'success' => true,
                    'message' => 'Mesero asignado correctamente',
                    'account_id' => $accountId
                ]);
            } else {
                throw new Exception('Error al asignar mesero');
            }

        } catch (Exception $e) {
            error_log("Error in assignWaiter: " . $e->getMessage());
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Render helper for views with layout
     * 
     * @param string $layout Layout path (relative to /app/views/)
     * @param array $data Data to pass to view
     */
    private function render($layout, $data = []) {
        extract($data);
        include APP_PATH . '/views/' . $layout . '.php';
    }

    /**
     * Show 500 error page
     * 
     * @param string $message Error message
     */
    private function show500($message = 'Error interno del servidor') {
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
}