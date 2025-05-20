<?php
class Supplier {
    private $db;
    private $table = 'suppliers';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create new supplier
     * @param array $data
     * @return int|bool
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO " . $this->table . " 
                    (company_name, contact_name, email, phone, address, abn) 
                    VALUES (:company_name, :contact_name, :email, :phone, :address, :abn)";
            
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':company_name', $data['company_name']);
            $stmt->bindParam(':contact_name', $data['contact_name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':phone', $data['phone']);
            $stmt->bindParam(':address', $data['address']);
            $stmt->bindParam(':abn', $data['abn']);
            
            $stmt->execute();
            return $this->db->lastInsertId();
        } catch(PDOException $e) {
            error_log("Supplier Creation Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get supplier by ID
     * @param int $supplierId
     * @return array|bool
     */
    public function getById($supplierId) {
        try {
            $sql = "SELECT * FROM " . $this->table . " WHERE supplier_id = :supplier_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':supplier_id', $supplierId);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Get Supplier Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all suppliers
     * @param string $status
     * @return array|bool
     */
    public function getAll($status = 'active') {
        try {
            $sql = "SELECT * FROM " . $this->table;
            if ($status) {
                $sql .= " WHERE status = :status";
            }
            $sql .= " ORDER BY company_name ASC";
            
            $stmt = $this->db->prepare($sql);
            if ($status) {
                $stmt->bindParam(':status', $status);
            }
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Get All Suppliers Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update supplier
     * @param int $supplierId
     * @param array $data
     * @return bool
     */
    public function update($supplierId, $data) {
        try {
            $sql = "UPDATE " . $this->table . " 
                    SET company_name = :company_name,
                        contact_name = :contact_name,
                        email = :email,
                        phone = :phone,
                        address = :address,
                        abn = :abn,
                        status = :status
                    WHERE supplier_id = :supplier_id";
            
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':supplier_id', $supplierId);
            $stmt->bindParam(':company_name', $data['company_name']);
            $stmt->bindParam(':contact_name', $data['contact_name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':phone', $data['phone']);
            $stmt->bindParam(':address', $data['address']);
            $stmt->bindParam(':abn', $data['abn']);
            $stmt->bindParam(':status', $data['status']);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Update Supplier Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete supplier
     * @param int $supplierId
     * @return bool
     */
    public function delete($supplierId) {
        try {
            $sql = "DELETE FROM " . $this->table . " WHERE supplier_id = :supplier_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':supplier_id', $supplierId);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Delete Supplier Error: " . $e->getMessage());
            return false;
        }
    }
}
?>
