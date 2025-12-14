<?php
/**
 * Orders Controller
 * Gestiona las operaciones de comandas
 * 
 * Patrón: MVC Controller
 * Security: AuthMiddleware requerido
 */

class OrdersController {

    /**
     * Vista para crear orden (Mesero)
     * Muestra el menú para tomar orden de una mesa
     */
    public function create() {
        AuthMiddleware::handle();

        // Obtener ID de cuenta desde parámetro
        $accountId = filter_input(INPUT_GET, 'account', FILTER_VALIDATE_INT);

        if (!$accountId) {
            $_SESSION['error'] = 'Cuenta no especificada';
            header('Location: ' . BASE_URL . '/tables');
            exit;
        }

        // Verificar que la cuenta existe y está abierta
        $accountModel = new Account();
        $account = $accountModel->getAccountById($accountId);

        if (!$account || $account['status'] !== 'open') {
            $_SESSION['error'] = 'Cuenta no válida o cerrada';
            header('Location: ' . BASE_URL . '/tables');
            exit;
        }

        // Obtener platillos y órdenes actuales
        $dishModel = new Dish();
        $orderModel = new Order();

        $categories = $dishModel->getDishesByCategory();
        $sideDishes = $dishModel->getAvailableSideDishes();
        $currentOrders = $orderModel->getOrdersByAccount($accountId);

        ob_start();
        include APP_PATH . '/views/orders/orders-create.php';
        $content = ob_get_clean();

        $this->render('layouts/dashboard', [
            'content' => $content,
            'pageTitle' => 'Tomar Orden - Mesa ' . $account['table_number'],
            'currentView' => 'orders',
            'customCSS' => ['pages/orders'],
            'customJS' => ['orders-waiter']
        ]);
    }

    /**
     * Vista de cocina (Chef)
     * Muestra todas las comandas pendientes
     */
    public function kitchen() {
        AuthMiddleware::handle();

        // Solo chefs y admin pueden acceder
        if (!in_array($_SESSION['role'], ['chef', 'admin'])) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        ob_start();
        include APP_PATH . '/views/orders/orders-kitchen.php';
        $content = ob_get_clean();

        $this->render('layouts/dashboard', [
            'content' => $content,
            'pageTitle' => 'Cocina - Comandas',
            'currentView' => 'kitchen',
            'customCSS' => ['pages/orders'],
            'customJS' => ['orders-kitchen']
        ]);
    }

    /**
     * API: Guarda una nueva orden
     * POST: account_id, items[]
     */
    public function store() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        try {
            // Validar entrada
            $accountId = filter_input(INPUT_POST, 'account_id', FILTER_VALIDATE_INT);
            $items = $_POST['items'] ?? [];

            if (!$accountId || empty($items)) {
                throw new Exception('Datos inválidos');
            }

            // Validar que es un array
            if (!is_array($items)) {
                $items = json_decode($items, true);
            }

            $orderModel = new Order();
            $dishModel = new Dish();
            $accountModel = new Account();
            $createdOrders = [];

            // Iniciar transacción (Pattern: Unit of Work)
            $db = Database::getInstance()->getConnection();
            $db->beginTransaction();

            foreach ($items as $item) {
                // Validar campos requeridos
                if (!isset($item['dish_id']) || !isset($item['quantity'])) {
                    throw new Exception('Item inválido en la orden');
                }

                $dishId = (int)$item['dish_id'];
                $quantity = (int)$item['quantity'];
                $sideDishId = isset($item['side_dish_id']) ? (int)$item['side_dish_id'] : null;
                $specialInstructions = trim($item['special_instructions'] ?? '');

                // Validar que el platillo existe y está disponible
                $dish = $dishModel->getDishById($dishId);
                if (!$dish || !$dish['is_available']) {
                    throw new Exception("Platillo #{$dishId} no disponible");
                }

                // Calcular subtotal
                $unitPrice = $dish['price'];
                $subtotal = $unitPrice * $quantity;

                // Crear orden
                $orderModel->accountId = $accountId;
                $orderModel->dishId = $dishId;
                $orderModel->sideDishId = $sideDishId;
                $orderModel->quantity = $quantity;
                $orderModel->unitPrice = $unitPrice;
                $orderModel->subtotal = $subtotal;
                $orderModel->specialInstructions = $specialInstructions;

                $orderId = $orderModel->create();

                if (!$orderId) {
                    throw new Exception('Error al crear orden');
                }

                $createdOrders[] = $orderId;
            }

            // Actualizar totales de la cuenta
            $accountModel->updateTotals($accountId);

            // Confirmar transacción
            $db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Órdenes enviadas a cocina',
                'orders' => $createdOrders
            ]);

        } catch (Exception $e) {
            // Rollback en caso de error
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }

            error_log("Error en store order: " . $e->getMessage());
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * API: Obtiene órdenes para cocina
     * GET: ?status=all|pending|preparing
     */
    public function getKitchenOrders() {
        header('Content-Type: application/json');

        try {
            $statusFilter = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING) ?? 'all';

            $orderModel = new Order();
            $orders = $orderModel->getKitchenOrders($statusFilter);
            $stats = $orderModel->getOrderStats();

            echo json_encode([
                'success' => true,
                'orders' => $orders,
                'stats' => $stats
            ]);

        } catch (Exception $e) {
            error_log("Error en getKitchenOrders: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener órdenes'
            ]);
        }
        exit;
    }

    /**
     * API: Actualiza el estado de una orden
     * POST: order_id, new_status
     */
    public function updateStatus() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        try {
            $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
            $newStatus = filter_input(INPUT_POST, 'new_status', FILTER_SANITIZE_STRING);

            if (!$orderId || !$newStatus) {
                throw new Exception('Datos inválidos');
            }

            $orderModel = new Order();
            $result = $orderModel->updateStatus($orderId, $newStatus);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Estado actualizado correctamente'
                ]);
            } else {
                throw new Exception('Error al actualizar estado');
            }

        } catch (Exception $e) {
            error_log("Error en updateStatus: " . $e->getMessage());
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Método helper para renderizar vistas con layout
     */
    private function render($layout, $data = []) {
        extract($data);
        include APP_PATH . '/views/' . $layout . '.php';
    }
}