<?php

class Sales {

    private $db;

    private $table = 'sales';



    public function __construct() {

        $this->db = Database::getInstance()->getConnection();

    }



    public function getById($saleId) {

        try {

            // Get sale header

            $sql = "SELECT s.*, c.first_name, c.last_name, c.company_name,

                           u.first_name as user_first_name, u.last_name as user_last_name 

                    FROM " . $this->table . " s

                    LEFT JOIN customers c ON s.customer_id = c.customer_id

                    LEFT JOIN users u ON s.user_id = u.user_id

                    WHERE s.sale_id = :sale_id";

            

            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':sale_id', $saleId);

            $stmt->execute();

            

            $sale = $stmt->fetch(PDO::FETCH_ASSOC);

            

            if($sale) {

                // Get sale items

                $sql = "SELECT si.*, p.product_name 

                        FROM sale_items si

                        LEFT JOIN products p ON si.product_id = p.product_id

                        WHERE si.sale_id = :sale_id";

                

                $stmt = $this->db->prepare($sql);

                $stmt->bindParam(':sale_id', $saleId);

                $stmt->execute();

                

                $sale['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            }

            

            return $sale;

        } catch(PDOException $e) {

            error_log("Get Sale Error: " . $e->getMessage());

            return false;

        }

    }

    public function create($data, $items) {

        try {

            $this->db->beginTransaction();



            // Insert sale header

            $sql = "INSERT INTO " . $this->table . " 

                    (customer_id, user_id, subtotal, tax_amount, total_amount, 

                    payment_method, payment_status, notes) 

                    VALUES (:customer_id, :user_id, :subtotal, :tax_amount, :total_amount,

                    :payment_method, :payment_status, :notes)";

            

            $stmt = $this->db->prepare($sql);

            

            $stmt->bindParam(':customer_id', $data['customer_id']);

            $stmt->bindParam(':user_id', $data['user_id']);

            $stmt->bindParam(':subtotal', $data['subtotal']);

            $stmt->bindParam(':tax_amount', $data['tax_amount']);

            $stmt->bindParam(':total_amount', $data['total_amount']);

            $stmt->bindParam(':payment_method', $data['payment_method']);

            $stmt->bindParam(':payment_status', $data['payment_status']);

            $stmt->bindParam(':notes', $data['notes']);

            

            $stmt->execute();

            $sale_id = $this->db->lastInsertId();



            // Insert sale items

            foreach($items as $item) {

                $sql = "INSERT INTO sale_items 

                        (sale_id, product_id, quantity, unit_price, tax_rate, 

                        tax_amount, subtotal, total_amount) 

                        VALUES (:sale_id, :product_id, :quantity, :unit_price, :tax_rate,

                        :tax_amount, :subtotal, :total_amount)";

                

                $stmt = $this->db->prepare($sql);

                

                $stmt->bindParam(':sale_id', $sale_id);

                $stmt->bindParam(':product_id', $item['product_id']);

                $stmt->bindParam(':quantity', $item['quantity']);

                $stmt->bindParam(':unit_price', $item['unit_price']);

                $stmt->bindParam(':tax_rate', $item['tax_rate']);

                $stmt->bindParam(':tax_amount', $item['tax_amount']);

                $stmt->bindParam(':subtotal', $item['subtotal']);

                $stmt->bindParam(':total_amount', $item['total_amount']);

                

                $stmt->execute();



                // Update product stock level

                $this->updateProductStock($item['product_id'], $item['quantity']);

            }



            $this->db->commit();

            return $sale_id;

        } catch(PDOException $e) {

            $this->db->rollBack();

            error_log("Sale Creation Error: " . $e->getMessage());

            return false;

        }

    }



    public function getAll($status = '') {

        try {

            $sql = "SELECT s.*, c.first_name, c.last_name, c.company_name,

                           u.first_name as user_first_name, u.last_name as user_last_name 

                    FROM " . $this->table . " s

                    LEFT JOIN customers c ON s.customer_id = c.customer_id

                    LEFT JOIN users u ON s.user_id = u.user_id";

            

            if($status) {

                $sql .= " WHERE s.payment_status = :status";

            }

            

            $sql .= " ORDER BY s.sale_date DESC";

            

            $stmt = $this->db->prepare($sql);

            if($status) {

                $stmt->bindParam(':status', $status);

            }

            $stmt->execute();

            

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch(PDOException $e) {

            error_log("Get All Sales Error: " . $e->getMessage());

            return false;

        }

    }



    public function getSalesByDateRange($startDate, $endDate) {

        try {

            $sql = "SELECT s.*, c.first_name, c.last_name, c.company_name,

                           u.first_name as user_first_name, u.last_name as user_last_name 

                    FROM " . $this->table . " s

                    LEFT JOIN customers c ON s.customer_id = c.customer_id

                    LEFT JOIN users u ON s.user_id = u.user_id

                    WHERE DATE(s.sale_date) BETWEEN :start_date AND :end_date

                    ORDER BY s.sale_date DESC";

            

            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':start_date', $startDate);

            $stmt->bindParam(':end_date', $endDate);

            $stmt->execute();

            

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch(PDOException $e) {

            error_log("Get Sales By Date Range Error: " . $e->getMessage());

            return false;

        }

    }

    public function getSalesByDateRangeAndStatus($startDate, $endDate, $status) {

        try {

            $sql = "SELECT s.*, c.first_name, c.last_name, c.company_name,

                           u.first_name as user_first_name, u.last_name as user_last_name 

                    FROM " . $this->table . " s

                    LEFT JOIN customers c ON s.customer_id = c.customer_id

                    LEFT JOIN users u ON s.user_id = u.user_id

                    WHERE DATE(s.sale_date) BETWEEN :start_date AND :end_date

                    AND s.payment_status = :status

                    ORDER BY s.sale_date DESC";

            

            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':start_date', $startDate);

            $stmt->bindParam(':end_date', $endDate);

            $stmt->bindParam(':status', $status);

            $stmt->execute();

            

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch(PDOException $e) {

            error_log("Get Sales By Date Range and Status Error: " . $e->getMessage());

            return false;

        }

    }



    public function getTotalSales($startDate = null, $endDate = null) {

        try {

            $sql = "SELECT COUNT(*) as count, 

                           COALESCE(SUM(total_amount), 0) as total,

                           COALESCE(SUM(tax_amount), 0) as tax_total

                    FROM " . $this->table;

            

            if($startDate && $endDate) {

                $sql .= " WHERE DATE(sale_date) BETWEEN :start_date AND :end_date";

            }

            

            $stmt = $this->db->prepare($sql);

            

            if($startDate && $endDate) {

                $stmt->bindParam(':start_date', $startDate);

                $stmt->bindParam(':end_date', $endDate);

            }

            

            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch(PDOException $e) {

            error_log("Get Total Sales Error: " . $e->getMessage());

            return ['count' => 0, 'total' => 0, 'tax_total' => 0];

        }

    }



    public function getTodaySales() {

        $today = date('Y-m-d');

        return $this->getTotalSales($today, $today);

    }



    public function getMonthSales() {

        try {

            $start_date = date('Y-m-01'); // First day of current month

            $end_date = date('Y-m-t');    // Last day of current month

            

            $sql = "SELECT COUNT(*) as count, 

                           COALESCE(SUM(total_amount), 0) as total,

                           COALESCE(SUM(tax_amount), 0) as tax_total

                    FROM " . $this->table . "

                    WHERE DATE(sale_date) BETWEEN :start_date AND :end_date";

            

            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':start_date', $start_date);

            $stmt->bindParam(':end_date', $end_date);

            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch(PDOException $e) {

            error_log("Get Month Sales Error: " . $e->getMessage());

            return ['count' => 0, 'total' => 0, 'tax_total' => 0];

        }

    }



    public function getYearSales() {

        try {

            $start_date = date('Y-01-01'); // First day of current year

            $end_date = date('Y-12-31');   // Last day of current year

            

            $sql = "SELECT COUNT(*) as count, 

                           COALESCE(SUM(total_amount), 0) as total,

                           COALESCE(SUM(tax_amount), 0) as tax_total

                    FROM " . $this->table . "

                    WHERE DATE(sale_date) BETWEEN :start_date AND :end_date";

            

            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':start_date', $start_date);

            $stmt->bindParam(':end_date', $end_date);

            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch(PDOException $e) {

            error_log("Get Year Sales Error: " . $e->getMessage());

            return ['count' => 0, 'total' => 0, 'tax_total' => 0];

        }

    }



    public function getSalesSummary($period = 'daily', $start_date = null, $end_date = null) {

        try {

            if (!$start_date) $start_date = date('Y-m-d', strtotime('-30 days'));

            if (!$end_date) $end_date = date('Y-m-d');



            $group_by = match($period) {

                'daily' => 'DATE(sale_date)',

                'weekly' => 'YEARWEEK(sale_date)',

                'monthly' => 'DATE_FORMAT(sale_date, "%Y-%m")',

                'yearly' => 'YEAR(sale_date)',

                default => 'DATE(sale_date)'

            };



            $sql = "SELECT 

                        {$group_by} as period,

                        COUNT(*) as count,

                        COALESCE(SUM(total_amount), 0) as total,

                        COALESCE(SUM(tax_amount), 0) as tax_total

                    FROM " . $this->table . "

                    WHERE DATE(sale_date) BETWEEN :start_date AND :end_date

                    GROUP BY {$group_by}

                    ORDER BY period ASC";



            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':start_date', $start_date);

            $stmt->bindParam(':end_date', $end_date);

            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch(PDOException $e) {

            error_log("Get Sales Summary Error: " . $e->getMessage());

            return [];

        }

    }

    public function getSalesByPaymentMethod($start_date = null, $end_date = null) {

        try {

            $sql = "SELECT 

                        payment_method,

                        COUNT(*) as count,

                        COALESCE(SUM(total_amount), 0) as total

                    FROM " . $this->table;



            if ($start_date && $end_date) {

                $sql .= " WHERE DATE(sale_date) BETWEEN :start_date AND :end_date";

            }



            $sql .= " GROUP BY payment_method";



            $stmt = $this->db->prepare($sql);

            

            if ($start_date && $end_date) {

                $stmt->bindParam(':start_date', $start_date);

                $stmt->bindParam(':end_date', $end_date);

            }



            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch(PDOException $e) {

            error_log("Get Sales By Payment Method Error: " . $e->getMessage());

            return [];

        }

    }



    public function getItemCount($saleId) {

        try {

            $sql = "SELECT COUNT(*) FROM sale_items WHERE sale_id = :sale_id";

            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':sale_id', $saleId);

            $stmt->execute();

            return (int)$stmt->fetchColumn();

        } catch(PDOException $e) {

            error_log("Get Item Count Error: " . $e->getMessage());

            return 0;

        }

    }



    public function update($saleId, $data, $items) {

        try {

            $this->db->beginTransaction();



            // Check if sale exists and is pending

            $sql = "SELECT payment_status FROM " . $this->table . " WHERE sale_id = :sale_id";

            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':sale_id', $saleId);

            $stmt->execute();

            

            $sale = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$sale || $sale['payment_status'] !== 'pending') {

                throw new Exception('Sale not found or cannot be updated');

            }



            // Update sale header

            $sql = "UPDATE " . $this->table . " 

                    SET customer_id = :customer_id,

                        subtotal = :subtotal,

                        tax_amount = :tax_amount,

                        total_amount = :total_amount,

                        payment_method = :payment_method,

                        notes = :notes

                    WHERE sale_id = :sale_id";

            

            $stmt = $this->db->prepare($sql);

            

            $stmt->bindParam(':sale_id', $saleId);

            $stmt->bindParam(':customer_id', $data['customer_id']);

            $stmt->bindParam(':subtotal', $data['subtotal']);

            $stmt->bindParam(':tax_amount', $data['tax_amount']);

            $stmt->bindParam(':total_amount', $data['total_amount']);

            $stmt->bindParam(':payment_method', $data['payment_method']);

            $stmt->bindParam(':notes', $data['notes']);

            

            $stmt->execute();



            // Get current items to restore stock

            $sql = "SELECT product_id, quantity FROM sale_items WHERE sale_id = :sale_id";

            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':sale_id', $saleId);

            $stmt->execute();

            $oldItems = $stmt->fetchAll(PDO::FETCH_ASSOC);



            // Restore stock levels

            foreach($oldItems as $item) {

                $this->updateProductStock($item['product_id'], -$item['quantity']);

            }



            // Delete existing items

            $sql = "DELETE FROM sale_items WHERE sale_id = :sale_id";

            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':sale_id', $saleId);

            $stmt->execute();



            // Insert new items

            foreach($items as $item) {

                $sql = "INSERT INTO sale_items 

                        (sale_id, product_id, quantity, unit_price, tax_rate, tax_amount, subtotal, total_amount) 

                        VALUES (:sale_id, :product_id, :quantity, :unit_price, :tax_rate, :tax_amount, :subtotal, :total_amount)";

                

                $stmt = $this->db->prepare($sql);

                

                $stmt->bindParam(':sale_id', $saleId);

                $stmt->bindParam(':product_id', $item['product_id']);

                $stmt->bindParam(':quantity', $item['quantity']);

                $stmt->bindParam(':unit_price', $item['unit_price']);

                $stmt->bindParam(':tax_rate', $item['tax_rate']);

                $stmt->bindParam(':tax_amount', $item['tax_amount']);

                $stmt->bindParam(':subtotal', $item['subtotal']);

                $stmt->bindParam(':total_amount', $item['total_amount']);

                

                $stmt->execute();



                // Update new stock levels

                $this->updateProductStock($item['product_id'], $item['quantity']);

            }



            $this->db->commit();

            return true;

        } catch(PDOException $e) {

            $this->db->rollBack();

            error_log("Sale Update Error: " . $e->getMessage());

            return false;

        }

    }



    private function updateProductStock($productId, $quantity) {

        try {

            $sql = "UPDATE products 

                    SET stock_level = stock_level - :quantity 

                    WHERE product_id = :product_id";

            

            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':product_id', $productId);

            $stmt->bindParam(':quantity', $quantity);

            return $stmt->execute();

        } catch(PDOException $e) {

            error_log("Update Stock Error: " . $e->getMessage());

            return false;

        }

    }



    public function delete($saleId) {

        try {

            $this->db->beginTransaction();



            // Check if sale exists and is pending

            $sql = "SELECT payment_status FROM " . $this->table . " WHERE sale_id = :sale_id";

            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':sale_id', $saleId);

            $stmt->execute();

            

            $sale = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$sale || $sale['payment_status'] !== 'pending') {

                throw new Exception('Sale not found or cannot be deleted');

            }



            // Get items to restore stock

            $sql = "SELECT product_id, quantity FROM sale_items WHERE sale_id = :sale_id";

            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':sale_id', $saleId);

            $stmt->execute();

            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);



            // Restore stock levels

            foreach($items as $item) {

                $this->updateProductStock($item['product_id'], -$item['quantity']);

            }



            // Delete sale items first

            $sql = "DELETE FROM sale_items WHERE sale_id = :sale_id";

            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':sale_id', $saleId);

            $stmt->execute();



            // Delete sale header

            $sql = "DELETE FROM " . $this->table . " WHERE sale_id = :sale_id";

            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':sale_id', $saleId);

            $stmt->execute();



            $this->db->commit();

            return true;

        } catch(PDOException $e) {

            $this->db->rollBack();

            error_log("Sale Delete Error: " . $e->getMessage());

            return false;

        }

    }



    public function markAsPaid($saleId) {

        try {

            $sql = "UPDATE " . $this->table . " 

                    SET payment_status = 'paid' 

                    WHERE sale_id = :sale_id AND payment_status = 'pending'";

            

            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':sale_id', $saleId);

            return $stmt->execute();

        } catch(PDOException $e) {

            error_log("Mark As Paid Error: " . $e->getMessage());

            return false;

        }

    }



    public function exists($saleId) {

        try {

            $sql = "SELECT COUNT(*) FROM " . $this->table . " WHERE sale_id = :sale_id";

            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':sale_id', $saleId);

            $stmt->execute();

            return $stmt->fetchColumn() > 0;

        } catch(PDOException $e) {

            error_log("Sale Exists Check Error: " . $e->getMessage());

            return false;

        }

    }



    public function isEditable($saleId) {

        try {

            $sql = "SELECT payment_status FROM " . $this->table . " WHERE sale_id = :sale_id";

            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':sale_id', $saleId);

            $stmt->execute();

            

            $sale = $stmt->fetch(PDO::FETCH_ASSOC);

            return $sale && $sale['payment_status'] === 'pending';

        } catch(PDOException $e) {

            error_log("Sale Editable Check Error: " . $e->getMessage());

            return false;

        }

    }

}





