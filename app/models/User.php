<?php
/**
 * Modelo de Usuario
 * Maneja autenticación y gestión de usuarios
 */

class User {
    private $db;
    private $table = 'users';

    // Propiedades del usuario
    public $id;
    public $roleId;
    public $username;
    public $passwordHash;
    public $fullName;
    public $email;
    public $phone;
    public $isActive;
    public $lastLogin;
    public $createdAt;
    public $updatedAt;

    /**
     * Constructor
     * Patrón: Dependency Injection (recibe conexión DB)
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Valida credenciales de usuario
     * @param string $username
     * @param string $password
     * @return array|false Datos del usuario o false si falla
     */
    public function validateCredentials($username, $password) {
        try {
            
            $query = "SELECT u.*, r.name as role_name
                      FROM {$this->table} u
                      INNER JOIN roles r ON u.role_id = r.id
                      WHERE u.username = :username
                      AND u.is_active = 1
                      AND u.deleted_at IS NULL
                      LIMIT 1";
    
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
    
            $user = $stmt->fetch();
            
            if ($user) {
                
                $passwordMatch = password_verify($password, $user['password_hash']);
                
                if ($passwordMatch) {
                    unset($user['password_hash']);
                    return $user;
                }
            }
            
            return false;
    
        } catch (PDOException $e) {
            error_log("Error en validateCredentials: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene usuario por ID
     * @param int $id
     * @return array|false
     */
    public function getUserById($id) {
        try {
            $query = "SELECT u.*, r.name as role_name
                      FROM {$this->table} u
                      INNER JOIN roles r ON u.role_id = r.id
                      WHERE u.id = :id
                      AND u.deleted_at IS NULL
                      LIMIT 1";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            return $stmt->fetch();

        } catch (PDOException $e) {
            error_log("Error en getUserById: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crea un nuevo usuario
     * @return bool
     */
    public function create() {
        try {
            $query = "INSERT INTO {$this->table}
                      (role_id, username, password_hash, full_name, email, phone)
                      VALUES (:role_id, :username, :password_hash, :full_name, :email, :phone)";

            $stmt = $this->db->prepare($query);

            // Hash del password (Security by Design)
            $hashedPassword = password_hash($this->passwordHash, PASSWORD_DEFAULT);

            $stmt->bindParam(':role_id', $this->roleId);
            $stmt->bindParam(':username', $this->username);
            $stmt->bindParam(':password_hash', $hashedPassword);
            $stmt->bindParam(':full_name', $this->fullName);
            $stmt->bindParam(':email', $this->email);
            $stmt->bindParam(':phone', $this->phone);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error en create user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza último login
     * @param int $userId
     * @return bool
     */
    public function updateLastLogin($userId) {
        try {
            $query = "UPDATE {$this->table}
                      SET last_login = NOW()
                      WHERE id = :id";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $userId);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error en updateLastLogin: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get users by role
     * @param string $roleName Role name (e.g., 'waiter', 'chef')
     * @return array List of users with that role
     */
    public function getUsersByRole($roleName) {
        try {
            $query = "SELECT u.id, u.username, u.full_name, u.email
                    FROM {$this->table} u
                    INNER JOIN roles r ON u.role_id = r.id
                    WHERE r.name = :role_name
                    AND u.is_active = 1
                    AND u.deleted_at IS NULL
                    ORDER BY u.full_name";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':role_name', $roleName);
            $stmt->execute();

            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("Error in getUsersByRole: " . $e->getMessage());
            return [];
        }
    }
}