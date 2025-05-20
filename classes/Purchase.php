<?php
class Purchase {
    private $db;
    private $table = 'purchases';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create new purchase
     * @param array $data
     * @return int|bool
     */
    public function create($data, $items) {
        try {
            $this->db->beginTransaction();

            // Insert purchase header
            $sql = "INSERT INTO " . $this->table . " 
                    (supplier_id, user_id, reference_no, subtotal, tax_amount, total_amount, status, notes) 
                    VALUES (:supplier_id, :user_id, :reference_no, :subtotal, :tax_amount, :total_amount, :status, :notes)";
            
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':supplier_id', $data['supplier_id']);
            $stmt->bindParam(':user_id', $data['user_id']);
            $stmt->bindParam(':reference_no', $data['reference_no']);
            $stmt->bindParam(':subtotal', $data['subtotal']);
            $stmt->bindParam(':tax_amount', $data['tax_amount']);
            $stmt->bindParam(':total_amount', $data['total_amount']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':notes', $data['notes']);
            
            $stmt->execute();
            $purchase_id = $this->db->lastInsertId();

            // Insert purchase items
            foreach($items as $item) {
                $sql = "INSERT INTO purchase_items 
                        (purchase_id, product_id, quantity, unit_price, subtotal, tax_amount) 
                        VALUES (:purchase_id, :product_id, :quantity, :unit_price, :subtotal, :tax_amount)";
                
                $stmt = $this->db->prepare($sql);
                
                $stmt->bindParam(':purchase_id', $purchase_id);
                $stmt->bindParam(':product_id', $item['product_id']);
                $stmt->bindParam(':quantity', $item['quantity']);
                $stmt->bindParam(':unit_price', $item['unit_price']);
                $stmt->bindParam(':subtotal', $item['subtotal']);
                $stmt->bindParam(':tax_amount', $item['tax_amount']);
                
                $stmt->execute();
            }

            $this->db->commit();
            return $purchase_id;
        } catch(PDOException $e) {
            $this->db->rollBack();
            error_log("Purchase Creation Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get purchase by ID with items
     * @param int $purchaseId
     * @return array|bool
     */
    public function getById($purchaseId) {
        try {
            // Get purchase header
            $sql = "SELECT p.*, s.company_name as supplier_name, u.first_name, u.last_name 
                    FROM " . $this->table . " p
                    LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                    LEFT JOIN users u ON p.user_id = u.user_id
                    WHERE p.purchase_id = :purchase_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':purchase_id', $purchaseId);
            $stmt->execute();
            
            $purchase = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($purchase) {
                // Get purchase items
                $sql = "SELECT pi.*, p.product_name 
                        FROM purchase_items pi
                        LEFT JOIN products p ON pi.product_id = p.product_id
                        WHERE pi.purchase_id = :purchase_id";
                
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':purchase_id', $purchaseId);
                $stmt->execute();
                
                $purchase['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            return $purchase;
        } catch(PDOException $e) {
            error_log("Get Purchase Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all purchases
     * @return array|bool
     */
    public function getAll() {
        try {
            $sql = "SELECT p.*, s.company_name as supplier_name, u.first_name, u.last_name 
                    FROM " . $this->table . " p
                    LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                    LEFT JOIN users u ON p.user_id = u.user_id
                    ORDER BY p.purchase_date DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Get All Purchases Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update purchase status
     * @param int $purchaseId
     * @param string $status
     * @return bool
     */
    public function updateStatus($purchaseId, $status) {
        try {
            $sql = "UPDATE " . $this->table . " 
                    SET status = :status 
                    WHERE purchase_id = :purchase_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':purchase_id', $purchaseId);
            $stmt->bindParam(':status', $status);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Update Purchase Status Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update purchase
     * @param int $purchaseId
     * @param array $data
     * @param array $items
     * @return bool
     */
    public function update($purchaseId, $data, $items) {
        try {
            $this->db->beginTransaction();

            // Check if purchase exists and is pending
            $sql = "SELECT status FROM " . $this->table . " WHERE purchase_id = :purchase_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':purchase_id', $purchaseId);
            $stmt->execute();
            
            $purchase = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$purchase || $purchase['status'] !== 'pending') {
                throw new Exception('Purchase order not found or cannot be updated');
            }

            // Update purchase header
            $sql = "UPDATE " . $this->table . " 
                    SET supplier_id = :supplier_id,
                        subtotal = :subtotal,
                        tax_amount = :tax_amount,
                        total_amount = :total_amount,
                        notes = :notes
                    WHERE purchase_id = :purchase_id";
            
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':purchase_id', $purchaseId);
            $stmt->bindParam(':supplier_id', $data['supplier_id']);
            $stmt->bindParam(':subtotal', $data['subtotal']);
            $stmt->bindParam(':tax_amount', $data['tax_amount']);
            $stmt->bindParam(':total_amount', $data['total_amount']);
            $stmt->bindParam(':notes', $data['notes']);
            
            $stmt->execute();

            // Delete existing items
            $sql = "DELETE FROM purchase_items WHERE purchase_id = :purchase_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':purchase_id', $purchaseId);
            $stmt->execute();

            // Insert new items
            foreach($items as $item) {
                $sql = "INSERT INTO purchase_items 
                        (purchase_id, product_id, quantity, unit_price, subtotal, tax_amount) 
                        VALUES (:purchase_id, :product_id, :quantity, :unit_price, :subtotal, :tax_amount)";
                
                $stmt = $this->db->prepare($sql);
                
                $stmt->bindParam(':purchase_id', $purchaseId);
                $stmt->bindParam(':product_id', $item['product_id']);
                $stmt->bindParam(':quantity', $item['quantity']);
                $stmt->bindParam(':unit_price', $item['unit_price']);
                $stmt->bindParam(':subtotal', $item['subtotal']);
                $stmt->bindParam(':tax_amount', $item['tax_amount']);
                
                $stmt->execute();
            }

            $this->db->commit();
            return true;
        } catch(PDOException $e) {
            $this->db->rollBack();
            error_log("Purchase Update Error: " . $e->getMessage());
            return false;
        } catch(Exception $e) {
            $this->db->rollBack();
            error_log("Purchase Update Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete purchase
     * @param int $purchaseId
     * @return bool
     */
    public function delete($purchaseId) {
        try {
            $this->db->beginTransaction();

            // Check if purchase exists and is pending
            $sql = "SELECT status FROM " . $this->table . " WHERE purchase_id = :purchase_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':purchase_id', $purchaseId);
            $stmt->execute();
            
            $purchase = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$purchase || $purchase['status'] !== 'pending') {
                throw new Exception('Purchase order not found or cannot be deleted');
            }

            // Delete purchase items first
            $sql = "DELETE FROM purchase_items WHERE purchase_id = :purchase_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':purchase_id', $purchaseId);
            $stmt->execute();

            // Delete purchase header
            $sql = "DELETE FROM " . $this->table . " WHERE purchase_id = :purchase_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':purchase_id', $purchaseId);
            $stmt->execute();

            $this->db->commit();
            return true;
        } catch(PDOException $e) {
            $this->db->rollBack();
            error_log("Purchase Delete Error: " . $e->getMessage());
            return false;
        } catch(Exception $e) {
            $this->db->rollBack();
            error_log("Purchase Delete Error: " . $e->getMessage());
            return false;
        }
    }
}
?>
