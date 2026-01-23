<?php
/**
 * Table Model
 * Maneja operaciones CRUD de mesas del restaurante
 * 
 * Patrón: Active Record Pattern
 * Conexión: Singleton Database
 */

class Table {
    private $db;
    private $table = 'tables';

    // Propiedades
    public $id;
    public $tableNumber;
    public $capacity;
    public $status;
    public $location;
    public $isActive;

    /**
     * Constructor
     * Patrón: Dependency Injection (recibe conexión DB)
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtiene todas las mesas activas
     * 
     * @return array Listado de mesas
     */
    public function getAllTables() {
        try {
            $query = "SELECT 
                        t.*,
                        a.id as account_id,
                        a.waiter_id,
                        a.opened_at,
                        u.full_name as waiter_name,
                        COUNT(o.id) as order_count,
                        COALESCE(a.total, 0) as current_total
                      FROM {$this->table} t
                      LEFT JOIN accounts a ON t.id = a.table_id AND a.status = 'open'
                      LEFT JOIN users u ON a.waiter_id = u.id
                      LEFT JOIN orders o ON a.id = o.account_id AND o.status != 'cancelled'
                      WHERE t.is_active = TRUE
                      GROUP BY t.id
                      ORDER BY CAST(t.table_number AS UNSIGNED)";

            $stmt = $this->db->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("Error en getAllTables: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene una mesa por ID
     * 
     * @param int $id ID de la mesa
     * @return array|false Datos de la mesa
     */
    public function getTableById($id) {
        try {
            $query = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch();

        } catch (PDOException $e) {
            error_log("Error en getTableById: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza el estado de una mesa
     * 
     * @param int $id ID de la mesa
     * @param string $status Nuevo estado
     * @return bool Éxito de la operación
     */
    public function updateStatus($id, $status) {
        try {
            $query = "UPDATE {$this->table} 
                      SET status = :status, 
                          updated_at = NOW() 
                      WHERE id = :id";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error en updateStatus: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene estadísticas de mesas
     * 
     * @return array Estadísticas (total, disponibles, ocupadas, reservadas)
     */
    public function getTableStats() {
        try {
            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                        SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied,
                        SUM(CASE WHEN status = 'reserved' THEN 1 ELSE 0 END) as reserved,
                        SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance
                      FROM {$this->table}
                      WHERE is_active = TRUE";

            $stmt = $this->db->prepare($query);
            $stmt->execute();

            return $stmt->fetch();

        } catch (PDOException $e) {
            error_log("Error en getTableStats: " . $e->getMessage());
            return [
                'total' => 0,
                'available' => 0,
                'occupied' => 0,
                'reserved' => 0,
                'maintenance' => 0
            ];
        }
    }

    /**
     * Crea una nueva mesa
     * 
     * @return bool Éxito de la operación
     */
    public function create() {
        try {
            $query = "INSERT INTO {$this->table}
                      (table_number, capacity, status, location)
                      VALUES (:table_number, :capacity, :status, :location)";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':table_number', $this->tableNumber);
            $stmt->bindParam(':capacity', $this->capacity, PDO::PARAM_INT);
            $stmt->bindParam(':status', $this->status);
            $stmt->bindParam(':location', $this->location);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error en create table: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene mesas disponibles para asignar
     * 
     * @return array Listado de mesas disponibles
     */
    public function getAvailableTables() {
        try {
            $query = "SELECT * FROM {$this->table}
                      WHERE status = 'available' 
                      AND is_active = TRUE
                      ORDER BY CAST(table_number AS UNSIGNED)";

            $stmt = $this->db->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("Error en getAvailableTables: " . $e->getMessage());
            return [];
        }
    }
}