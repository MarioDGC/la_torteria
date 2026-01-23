<?php
/**
 * Account Model
 * Maneja las cuentas asociadas a las mesas
 * 
 * Patrón: Active Record
 * Propósito: Una cuenta agrupa todas las órdenes de una mesa
 */

class Account {
    private $db;
    private $table = 'accounts';

    // Propiedades
    public $id;
    public $tableId;
    public $waiterId;
    public $openedAt;
    public $closedAt;
    public $subtotal;
    public $tax;
    public $discount;
    public $total;
    public $paymentMethod;
    public $status;
    public $notes;

    /**
     * Constructor
     * Patrón: Dependency Injection
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Crea o obtiene una cuenta existente para una mesa
     * Si la mesa ya tiene una cuenta abierta, la retorna
     * Si no, crea una nueva
     * 
     * @param int $tableId ID de la mesa
     * @param int $waiterId ID del mesero
     * @return int|false ID de la cuenta o false si falla
     */
    public function createOrGetAccount($tableId, $waiterId) {
        try {
            // Verificar si ya existe una cuenta abierta para esta mesa
            $query = "SELECT id FROM {$this->table}
                      WHERE table_id = :table_id 
                      AND status = 'open'
                      LIMIT 1";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':table_id', $tableId, PDO::PARAM_INT);
            $stmt->execute();

            $existing = $stmt->fetch();

            if ($existing) {
                return $existing['id'];
            }

            // Crear nueva cuenta
            $query = "INSERT INTO {$this->table}
                      (table_id, waiter_id, opened_at, status)
                      VALUES (:table_id, :waiter_id, NOW(), 'open')";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':table_id', $tableId, PDO::PARAM_INT);
            $stmt->bindParam(':waiter_id', $waiterId, PDO::PARAM_INT);

            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }

            return false;

        } catch (PDOException $e) {
            error_log("Error en createOrGetAccount: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene una cuenta por ID con sus detalles
     * 
     * @param int $id ID de la cuenta
     * @return array|false Datos de la cuenta
     */
    public function getAccountById($id) {
        try {
            $query = "SELECT 
                        a.*,
                        t.table_number,
                        u.full_name as waiter_name,
                        COUNT(o.id) as order_count
                      FROM {$this->table} a
                      JOIN tables t ON a.table_id = t.id
                      JOIN users u ON a.waiter_id = u.id
                      LEFT JOIN orders o ON a.id = o.account_id
                      WHERE a.id = :id
                      GROUP BY a.id
                      LIMIT 1";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch();

        } catch (PDOException $e) {
            error_log("Error en getAccountById: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza los totales de una cuenta
     * Recalcula subtotal, impuestos y total
     * 
     * @param int $accountId ID de la cuenta
     * @return bool Éxito de la operación
     */
    public function updateTotals($accountId) {
        try {
            // Calcular subtotal desde las órdenes
            $query = "SELECT SUM(subtotal) as subtotal
                      FROM orders
                      WHERE account_id = :account_id
                      AND status != 'cancelled'";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':account_id', $accountId, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch();
            $subtotal = $result['subtotal'] ?? 0;

            // Calcular impuesto (16% IVA)
            $tax = $subtotal * 0.16;
            $total = $subtotal + $tax;

            // Actualizar cuenta
            $query = "UPDATE {$this->table}
                      SET subtotal = :subtotal,
                          tax = :tax,
                          total = :total,
                          updated_at = NOW()
                      WHERE id = :id";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':subtotal', $subtotal);
            $stmt->bindParam(':tax', $tax);
            $stmt->bindParam(':total', $total);
            $stmt->bindParam(':id', $accountId, PDO::PARAM_INT);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error en updateTotals: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cierra una cuenta
     * 
     * @param int $accountId ID de la cuenta
     * @param string $paymentMethod Método de pago
     * @return bool Éxito de la operación
     */
    public function closeAccount($accountId, $paymentMethod = 'cash') {
        try {
            $query = "UPDATE {$this->table}
                      SET status = 'closed',
                          closed_at = NOW(),
                          payment_method = :payment_method,
                          updated_at = NOW()
                      WHERE id = :id";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':payment_method', $paymentMethod);
            $stmt->bindParam(':id', $accountId, PDO::PARAM_INT);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error en closeAccount: " . $e->getMessage());
            return false;
        }
    }
}