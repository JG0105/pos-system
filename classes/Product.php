<?php
class Product {
    private $db;
    private $table = 'products';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all products
     * @return array
     */
    public function getAll() {
        try {
            $sql = "SELECT * FROM " . $this->table . " ORDER BY product_name";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Product GetAll Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get product by ID
     * @param int $id
     * @return array
     */
    public function getById($id) {
        try {
            $sql = "SELECT * FROM " . $this->table . " WHERE product_id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Product GetById Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create new product
     * @param array $data
     * @return int|bool
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO " . $this->table . " 
                    (sku, product_name, description, unit_price, 
                    cost_price, stock_level, min_stock_level, 
                    unit_of_measure, tax_rate, status) 
                    VALUES 
                    (:sku, :product_name, :description, :unit_price, 
                    :cost_price, :stock_level, :min_stock_level, 
                    :unit_of_measure, :tax_rate, 'active')";

            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':sku', $data['sku'], PDO::PARAM_STR);
            $stmt->bindParam(':product_name', $data['product_name'], PDO::PARAM_STR);
            $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
            $stmt->bindParam(':unit_price', $data['unit_price'], PDO::PARAM_STR);
            $stmt->bindParam(':cost_price', $data['cost_price'], PDO::PARAM_STR);
            $stmt->bindParam(':stock_level', $data['stock_level'], PDO::PARAM_INT);
            $stmt->bindParam(':min_stock_level', $data['min_stock_level'], PDO::PARAM_INT);
            $stmt->bindParam(':unit_of_measure', $data['unit_of_measure'], PDO::PARAM_STR);
            $stmt->bindParam(':tax_rate', $data['tax_rate'], PDO::PARAM_STR);

            $stmt->execute();
            return $this->db->lastInsertId();
        } catch(PDOException $e) {
            error_log("Product Create Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update product
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        try {
            $sql = "UPDATE " . $this->table . " SET 
                    sku = :sku,
                    product_name = :product_name,
                    description = :description,
                    unit_price = :unit_price,
                    cost_price = :cost_price,
                    stock_level = :stock_level,
                    min_stock_level = :min_stock_level,
                    unit_of_measure = :unit_of_measure,
                    tax_rate = :tax_rate,
                    status = :status
                    WHERE product_id = :id";

            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':sku', $data['sku'], PDO::PARAM_STR);
            $stmt->bindParam(':product_name', $data['product_name'], PDO::PARAM_STR);
            $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
            $stmt->bindParam(':unit_price', $data['unit_price'], PDO::PARAM_STR);
            $stmt->bindParam(':cost_price', $data['cost_price'], PDO::PARAM_STR);
            $stmt->bindParam(':stock_level', $data['stock_level'], PDO::PARAM_INT);
            $stmt->bindParam(':min_stock_level', $data['min_stock_level'], PDO::PARAM_INT);
            $stmt->bindParam(':unit_of_measure', $data['unit_of_measure'], PDO::PARAM_STR);
            $stmt->bindParam(':tax_rate', $data['tax_rate'], PDO::PARAM_STR);
            $stmt->bindParam(':status', $data['status'], PDO::PARAM_STR);

            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Product Update Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if SKU exists
     * @param string $sku
     * @param int|null $excludeId
     * @return bool
     */
    public function skuExists($sku, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) FROM " . $this->table . " WHERE sku = :sku";
            if ($excludeId) {
                $sql .= " AND product_id != :id";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':sku', $sku, PDO::PARAM_STR);
            if ($excludeId) {
                $stmt->bindParam(':id', $excludeId, PDO::PARAM_INT);
            }
            $stmt->execute();
            
            return $stmt->fetchColumn() > 0;
        } catch(PDOException $e) {
            error_log("SKU Check Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete product
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        try {
            $sql = "DELETE FROM " . $this->table . " WHERE product_id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Product Delete Error: " . $e->getMessage());
            return false;
        }
    }
}
?>
