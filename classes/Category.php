<?php
class Category {
    private $db;
    private $table = 'categories';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all categories
     */
    public function getAll() {
        try {
            $sql = "SELECT c.*, 
                    (SELECT COUNT(*) FROM products WHERE category_id = c.category_id) as product_count 
                    FROM " . $this->table . " c 
                    ORDER BY c.category_name";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Category GetAll Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create new category
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO " . $this->table . " (category_name, description) 
                    VALUES (:category_name, :description)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':category_name', $data['category_name']);
            $stmt->bindParam(':description', $data['description']);
            
            $stmt->execute();
            return $this->db->lastInsertId();
        } catch(PDOException $e) {
            error_log("Category Create Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get category by ID
     */
    public function getById($id) {
        try {
            $sql = "SELECT * FROM " . $this->table . " WHERE category_id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Category GetById Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update category
     */
    public function update($id, $data) {
        try {
            $sql = "UPDATE " . $this->table . " 
                    SET category_name = :category_name, 
                        description = :description 
                    WHERE category_id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':category_name', $data['category_name']);
            $stmt->bindParam(':description', $data['description']);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Category Update Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete category
     */
    public function delete($id) {
        try {
            // First check if category has products
            $sql = "SELECT COUNT(*) FROM products WHERE category_id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Cannot delete category: It has associated products");
            }
            
            $sql = "DELETE FROM " . $this->table . " WHERE category_id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Category Delete Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if category name exists
     */
    public function nameExists($name, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) FROM " . $this->table . " WHERE category_name = :name";
            if ($excludeId) {
                $sql .= " AND category_id != :id";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':name', $name);
            if ($excludeId) {
                $stmt->bindParam(':id', $excludeId);
            }
            $stmt->execute();
            
            return $stmt->fetchColumn() > 0;
        } catch(PDOException $e) {
            error_log("Category Name Check Error: " . $e->getMessage());
            return false;
        }
    }
}
?>
