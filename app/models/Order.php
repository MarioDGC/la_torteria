<?php
/**
 * Order Model
 * Maneja operaciones de comandas/órdenes
 * 
 * Patrón: Active Record Pattern
 */

class Order {
    private $db;
    private $table = 'orders';

    public $id;
    public $accountId;
    public $dishId;
    public $sideDishId;
    public $quantity;
    public $unitPrice;
    public $subtotal;
    public $status;
    public $specialInstructions;

    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Crea una nueva orden
     * 
     * @return int|false ID de la orden creada
     */
    public function create() {
        try {
            $query = "INSERT INTO {$this->table}
                    (account_id, dish_id, side_dish_id, quantity, unit_price, 
                    subtotal, special_instructions, ordered_at, status)
                    VALUES (:account_id, :dish_id, :side_dish_id, :quantity, 
                            :unit_price, :subtotal, :special_instructions, 
                            NOW(), 'pending')";
            
            $stmt = $this->db->prepare($query);
            
            $stmt->bindParam(':account_id', $this->accountId, PDO::PARAM_INT);
            $stmt->bindParam(':dish_id', $this->dishId, PDO::PARAM_INT);
            
            // ✅ Manejo correcto de NULL para side_dish_id
            if ($this->sideDishId !== null) {
                $stmt->bindParam(':side_dish_id', $this->sideDishId, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':side_dish_id', null, PDO::PARAM_NULL);
            }
            
            $stmt->bindParam(':quantity', $this->quantity, PDO::PARAM_INT);
            $stmt->bindParam(':unit_price', $this->unitPrice);
            $stmt->bindParam(':subtotal', $this->subtotal);
            $stmt->bindParam(':special_instructions', $this->specialInstructions);

            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }

            return false;

        } catch (PDOException $e) {
            error_log("Error en Order::create: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene órdenes de una cuenta específica
     * 
     * @param int $accountId ID de la cuenta
     * @return array Lista de órdenes
     */
    public function getOrdersByAccount($accountId) {
        try {
            $query = "SELECT 
                        o.*,
                        d.name as dish_name,
                        d.description as dish_description,
                        sd.name as side_dish_name,
                        c.name as category_name
                      FROM {$this->table} o
                      INNER JOIN dishes d ON o.dish_id = d.id
                      LEFT JOIN side_dishes sd ON o.side_dish_id = sd.id
                      INNER JOIN categories c ON d.category_id = c.id
                      WHERE o.account_id = :account_id
                      AND o.status != 'cancelled'
                      ORDER BY o.ordered_at DESC";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':account_id', $accountId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("Error en getOrdersByAccount: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene todas las órdenes pendientes para cocina
     * Patrón: Query Object
     * 
     * @param string $statusFilter Filtro de estado
     * @return array Lista de órdenes
     */
    public function getKitchenOrders($statusFilter = 'all') {
        try {
            $query = "SELECT 
                        o.*,
                        d.name as dish_name,
                        d.preparation_time,
                        sd.name as side_dish_name,
                        t.table_number,
                        a.waiter_id,
                        u.full_name as waiter_name,
                        TIMESTAMPDIFF(MINUTE, o.ordered_at, NOW()) as waiting_minutes
                      FROM {$this->table} o
                      INNER JOIN dishes d ON o.dish_id = d.id
                      LEFT JOIN side_dishes sd ON o.side_dish_id = sd.id
                      INNER JOIN accounts a ON o.account_id = a.id
                      INNER JOIN tables t ON a.table_id = t.id
                      INNER JOIN users u ON a.waiter_id = u.id
                      WHERE a.status = 'open'";

            // Aplicar filtro de estado
            if ($statusFilter !== 'all') {
                $query .= " AND o.status = :status";
            } else {
                $query .= " AND o.status IN ('pending', 'preparing')";
            }

            $query .= " ORDER BY o.ordered_at ASC";

            $stmt = $this->db->prepare($query);
            
            if ($statusFilter !== 'all') {
                $stmt->bindParam(':status', $statusFilter);
            }
            
            $stmt->execute();

            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("Error en getKitchenOrders: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualiza el estado de una orden
     * 
     * @param int $id ID de la orden
     * @param string $newStatus Nuevo estado
     * @return bool
     */
    public function updateStatus($id, $newStatus) {
        try {
            // Validar estados permitidos
            $allowedStatuses = ['pending', 'preparing', 'ready', 'served', 'cancelled'];
            if (!in_array($newStatus, $allowedStatuses)) {
                throw new Exception("Estado no válido: {$newStatus}");
            }

            $query = "UPDATE {$this->table} 
                      SET status = :status,
                          updated_at = NOW()";

            // Agregar timestamp según el estado
            if ($newStatus === 'preparing') {
                $query .= ", prepared_at = NOW()";
            } elseif ($newStatus === 'ready' || $newStatus === 'served') {
                $query .= ", served_at = NOW()";
            }

            $query .= " WHERE id = :id";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':status', $newStatus);

            return $stmt->execute();

        } catch (Exception $e) {
            error_log("Error en updateStatus: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancela una orden
     * 
     * @param int $id ID de la orden
     * @param int $userId ID del usuario que cancela
     * @return bool
     */
    public function cancel($id, $userId) {
        try {
            // Log de auditoría
            error_log("Orden #{$id} cancelada por usuario #{$userId}");

            return $this->updateStatus($id, 'cancelled');

        } catch (Exception $e) {
            error_log("Error en cancel: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene estadísticas de órdenes por estado
     * 
     * @return array Estadísticas
     */
    public function getOrderStats() {
        try {
            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'preparing' THEN 1 ELSE 0 END) as preparing,
                        SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) as ready,
                        AVG(TIMESTAMPDIFF(MINUTE, ordered_at, served_at)) as avg_prep_time
                      FROM {$this->table}
                      WHERE DATE(ordered_at) = CURDATE()
                      AND status != 'cancelled'";

            $stmt = $this->db->prepare($query);
            $stmt->execute();

            return $stmt->fetch();

        } catch (PDOException $e) {
            error_log("Error en getOrderStats: " . $e->getMessage());
            return [
                'total' => 0,
                'pending' => 0,
                'preparing' => 0,
                'ready' => 0,
                'avg_prep_time' => 0
            ];
        }
    }
}