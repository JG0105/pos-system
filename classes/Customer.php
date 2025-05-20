<?php
class Customer {
    private $db;
    private $table = 'customers';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all customers
     */
    public function getAll() {
        try {
            $sql = "SELECT c.*, 
                    (SELECT COUNT(*) FROM sales WHERE customer_id = c.customer_id) as sales_count,
                    (SELECT SUM(total_amount) FROM sales WHERE customer_id = c.customer_id) as total_spent
                    FROM " . $this->table . " c 
                    ORDER BY c.last_name, c.first_name";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Customer GetAll Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create new customer
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO " . $this->table . " 
                    (first_name, last_name, email, phone, address, company_name, abn) 
                    VALUES 
                    (:first_name, :last_name, :email, :phone, :address, :company_name, :abn)";
            
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':first_name', $data['first_name']);
            $stmt->bindParam(':last_name', $data['last_name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':phone', $data['phone']);
            $stmt->bindParam(':address', $data['address']);
            $stmt->bindParam(':company_name', $data['company_name']);
            $stmt->bindParam(':abn', $data['abn']);
            
            $stmt->execute();
            return $this->db->lastInsertId();
        } catch(PDOException $e) {
            error_log("Customer Create Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get customer by ID
     */
    public function getById($id) {
        try {
            $sql = "SELECT * FROM " . $this->table . " WHERE customer_id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Customer GetById Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update customer
     */
    public function update($id, $data) {
        try {
            $sql = "UPDATE " . $this->table . " SET 
                    first_name = :first_name,
                    last_name = :last_name,
                    email = :email,
                    phone = :phone,
                    address = :address,
                    company_name = :company_name,
                    abn = :abn
                    WHERE customer_id = :id";
            
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':first_name', $data['first_name']);
            $stmt->bindParam(':last_name', $data['last_name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':phone', $data['phone']);
            $stmt->bindParam(':address', $data['address']);
            $stmt->bindParam(':company_name', $data['company_name']);
            $stmt->bindParam(':abn', $data['abn']);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Customer Update Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete customer
     */
    public function delete($id) {
        try {
            // First check if customer has sales
            $sql = "SELECT COUNT(*) FROM sales WHERE customer_id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Cannot delete customer: They have associated sales records");
            }
            
            $sql = "DELETE FROM " . $this->table . " WHERE customer_id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Customer Delete Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if email exists
     */
    public function emailExists($email, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) FROM " . $this->table . " WHERE email = :email";
            if ($excludeId) {
                $sql .= " AND customer_id != :id";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':email', $email);
            if ($excludeId) {
                $stmt->bindParam(':id', $excludeId);
            }
            $stmt->execute();
            
            return $stmt->fetchColumn() > 0;
        } catch(PDOException $e) {
            error_log("Customer Email Check Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Search customers
     */
    public function search($term) {
        try {
            $term = "%$term%";
            $sql = "SELECT * FROM " . $this->table . " 
                    WHERE first_name LIKE :term 
                    OR last_name LIKE :term 
                    OR email LIKE :term 
                    OR phone LIKE :term 
                    OR company_name LIKE :term 
                    ORDER BY last_name, first_name";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':term', $term);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Customer Search Error: " . $e->getMessage());
            return false;
        }
    }
}
?>
