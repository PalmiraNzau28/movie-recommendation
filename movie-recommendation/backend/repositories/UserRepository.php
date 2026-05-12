<?php
require_once __DIR__ . '/../config/database.php';

class UserRepository {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Cria um novo usuário com validação de unicidade de email
     * @param string $name
     * @param string $email
     * @param string $password_hash (já hasheado)
     * @return int|false ID do usuário ou false se email duplicado
     */
    public function create($name, $email, $password_hash) {
        try {
            // Verificar se email já existe
            $checkStmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $checkStmt->execute([$email]);
            if ($checkStmt->fetch()) {
                return false; // Email já registrado
            }
            
            $sql = "INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$name, $email, $password_hash]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("UserRepository::create error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Busca usuário por email (para login)
     * @param string $email
     * @return array|false
     */
    public function findByEmail($email) {
        try {
            $sql = "SELECT id, name, email, password_hash, created_at FROM users WHERE email = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("UserRepository::findByEmail error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Busca usuário por ID
     * @param int $id
     * @return array|false
     */
    public function findById($id) {
        try {
            $sql = "SELECT id, name, email, created_at FROM users WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("UserRepository::findById error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza dados do usuário (exceto senha)
     * @param int $id
     * @param string $name
     * @return bool
     */
    public function update($id, $name) {
        try {
            $sql = "UPDATE users SET name = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$name, $id]);
        } catch (PDOException $e) {
            error_log("UserRepository::update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza senha do usuário
     * @param int $id
     * @param string $new_password_hash
     * @return bool
     */
    public function updatePassword($id, $new_password_hash) {
        try {
            $sql = "UPDATE users SET password_hash = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$new_password_hash, $id]);
        } catch (PDOException $e) {
            error_log("UserRepository::updatePassword error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se email já existe (para validação no registro)
     * @param string $email
     * @return bool
     */
    public function emailExists($email) {
        try {
            $sql = "SELECT 1 FROM users WHERE email = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);
            return (bool)$stmt->fetch();
        } catch (PDOException $e) {
            error_log("UserRepository::emailExists error: " . $e->getMessage());
            return false;
        }
    }
}
?>
