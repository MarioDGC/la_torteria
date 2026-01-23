<?php
/**
 * Dish Model
 * Maneja operaciones CRUD de platillos del menú
 * 
 * Patrón: Active Record Pattern
 * Conexión: Singleton Database
 */

class Dish {
    private $db;
    private $table = 'dishes';

    public $id;
    public $categoryId;
    public $name;
    public $description;
    public $price;
    public $requiresSideDish;
    public $imageUrl;
    public $isAvailable;

    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtiene todos los platillos disponibles agrupados por categoría
     * 
     * @return array Platillos agrupados por categoría
     */
    public function getDishesByCategory() {
        try {
            $query = "SELECT 
                        c.id as category_id,
                        c.name as category_name,
                        c.display_order,
                        d.id,
                        d.name,
                        d.description,
                        d.price,
                        d.requires_side_dish,
                        d.image_url,
                        d.is_available
                      FROM categories c
                      INNER JOIN {$this->table} d ON c.id = d.category_id
                      WHERE c.is_active = TRUE 
                      AND d.is_active = TRUE
                      AND d.is_available = TRUE
                      ORDER BY c.display_order, d.name";

            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $results = $stmt->fetchAll();

            // Agrupar por categoría (Pattern: Data Transfer Object)
            $grouped = [];
            foreach ($results as $row) {
                $categoryId = $row['category_id'];
                
                if (!isset($grouped[$categoryId])) {
                    $grouped[$categoryId] = [
                        'id' => $row['category_id'],
                        'name' => $row['category_name'],
                        'dishes' => []
                    ];
                }

                $grouped[$categoryId]['dishes'][] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'price' => $row['price'],
                    'requires_side_dish' => $row['requires_side_dish'],
                    'image_url' => $row['image_url'],
                    'is_available' => $row['is_available']
                ];
            }

            return array_values($grouped);

        } catch (PDOException $e) {
            error_log("Error en getDishesByCategory: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene un platillo por ID
     * 
     * @param int $id ID del platillo
     * @return array|false Datos del platillo
     */
    public function getDishById($id) {
        try {
            $query = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch();

        } catch (PDOException $e) {
            error_log("Error en getDishById: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene guisados disponibles
     * 
     * @return array Lista de guisados
     */
    public function getAvailableSideDishes() {
        try {
            $query = "SELECT id, name, description 
                      FROM side_dishes 
                      WHERE is_active = TRUE 
                      AND is_available = TRUE
                      ORDER BY name";

            $stmt = $this->db->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("Error en getAvailableSideDishes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verifica si un platillo está disponible
     * 
     * @param int $id ID del platillo
     * @return bool
     */
    public function isAvailable($id) {
        try {
            $query = "SELECT is_available FROM {$this->table} 
                      WHERE id = :id AND is_active = TRUE LIMIT 1";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch();
            return $result ? (bool)$result['is_available'] : false;

        } catch (PDOException $e) {
            error_log("Error en isAvailable: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene todos los platillos con información de categoría
     * Para API endpoints
     * 
     * @return array
     */
    public function getAllWithCategory() {
        try {
            $query = "SELECT 
                        d.*,
                        c.id as category_id,
                        c.name as category_name
                    FROM {$this->table} d
                    INNER JOIN categories c ON d.category_id = c.id
                    WHERE d.is_active = TRUE
                    AND c.is_active = TRUE
                    ORDER BY c.display_order, d.name";

            $stmt = $this->db->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("Error en getAllWithCategory: " . $e->getMessage());
            return [];
        }
    }
}