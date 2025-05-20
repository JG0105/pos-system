<?php
class User {
    private $db;
    private $table = 'users';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create new user
     * @param array $data
     * @return int|bool
     */
    public function create($data) {
        try {
            // Hash the password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO " . $this->table . " 
                    (username, password, first_name, last_name, role) 
                    VALUES (:username, :password, :first_name, :last_name, :role)";
            
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':username', $data['username']);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':first_name', $data['first_name']);
            $stmt->bindParam(':last_name', $data['last_name']);
            $stmt->bindParam(':role', $data['role']);
            
            $stmt->execute();
            return $this->db->lastInsertId();
        } catch(PDOException $e) {
            error_log("User Creation Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Login user
     * @param string $username
     * @param string $password
     * @return bool
     */
    public function login($username, $password) {
        try {
            $sql = "SELECT * FROM " . $this->table . " WHERE username = :username AND status = 'active'";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($user && password_verify($password, $user['password'])) {
                // Update last login time
                $this->updateLastLogin($user['user_id']);
                
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['first_name'] = $user['first_name'];
                
                return true;
            }
            return false;
        } catch(PDOException $e) {
            error_log("Login Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update last login time
     * @param int $userId
     */
    private function updateLastLogin($userId) {
        try {
            $sql = "UPDATE " . $this->table . " SET last_login = CURRENT_TIMESTAMP WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
        } catch(PDOException $e) {
            error_log("Update Last Login Error: " . $e->getMessage());
        }
    }

    /**
     * Check if username exists
     * @param string $username
     * @return bool
     */
    public function usernameExists($username) {
        try {
            $sql = "SELECT COUNT(*) FROM " . $this->table . " WHERE username = :username";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            return $stmt->fetchColumn() > 0;
        } catch(PDOException $e) {
            error_log("Username Check Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user by ID
     * @param int $userId
     * @return array|bool
     */
    public function getById($userId) {
        try {
            $sql = "SELECT user_id, username, first_name, last_name, role, status, last_login, created_at 
                    FROM " . $this->table . " 
                    WHERE user_id = :user_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Get User Error: " . $e->getMessage());
            return false;
        }
    }
}
?>
