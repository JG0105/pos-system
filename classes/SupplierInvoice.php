<?php
class SupplierInvoice {
    private $db;
    private $table = 'supplier_invoices';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create new supplier invoice
     * @param array $data
     * @param array $items
     * @return int|bool
     */
    public function create($data, $items) {
        try {
            $this->db->beginTransaction();

            // Insert invoice header
            $sql = "INSERT INTO " . $this->table . " 
                    (supplier_id, user_id, invoice_number, invoice_date, due_date, 
                    subtotal, tax_amount, total_amount, payment_status, payment_due, notes) 
                    VALUES (:supplier_id, :user_id, :invoice_number, :invoice_date, :due_date,
                    :subtotal, :tax_amount, :total_amount, :payment_status, :payment_due, :notes)";
            
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':supplier_id', $data['supplier_id']);
            $stmt->bindParam(':user_id', $data['user_id']);
            $stmt->bindParam(':invoice_number', $data['invoice_number']);
            $stmt->bindParam(':invoice_date', $data['invoice_date']);
            $stmt->bindParam(':due_date', $data['due_date']);
            $stmt->bindParam(':subtotal', $data['subtotal']);
            $stmt->bindParam(':tax_amount', $data['tax_amount']);
            $stmt->bindParam(':total_amount', $data['total_amount']);
            $stmt->bindParam(':payment_status', $data['payment_status']);
            $stmt->bindParam(':payment_due', $data['total_amount']); // Initially, payment_due equals total_amount
            $stmt->bindParam(':notes', $data['notes']);
            
            $stmt->execute();
            $invoice_id = $this->db->lastInsertId();

            // Insert invoice items
            foreach($items as $item) {
                $sql = "INSERT INTO supplier_invoice_items 
                        (invoice_id, product_id, quantity, unit_price, tax_rate, tax_amount, subtotal) 
                        VALUES (:invoice_id, :product_id, :quantity, :unit_price, :tax_rate, :tax_amount, :subtotal)";
                
                $stmt = $this->db->prepare($sql);
                
                $stmt->bindParam(':invoice_id', $invoice_id);
                $stmt->bindParam(':product_id', $item['product_id']);
                $stmt->bindParam(':quantity', $item['quantity']);
                $stmt->bindParam(':unit_price', $item['unit_price']);
                $stmt->bindParam(':tax_rate', $item['tax_rate']);
                $stmt->bindParam(':tax_amount', $item['tax_amount']);
                $stmt->bindParam(':subtotal', $item['subtotal']);
                
                $stmt->execute();

                // Update product cost price if it's different
                $this->updateProductCostPrice($item['product_id'], $item['unit_price']);
            }

            $this->db->commit();
            return $invoice_id;
        } catch(PDOException $e) {
            $this->db->rollBack();
            error_log("Supplier Invoice Creation Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get invoice by ID with items
     * @param int $invoiceId
     * @return array|bool
     */
    public function getById($invoiceId) {
        try {
            // Get invoice header
            $sql = "SELECT si.*, s.company_name as supplier_name, u.first_name, u.last_name 
                    FROM " . $this->table . " si
                    LEFT JOIN suppliers s ON si.supplier_id = s.supplier_id
                    LEFT JOIN users u ON si.user_id = u.user_id
                    WHERE si.invoice_id = :invoice_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':invoice_id', $invoiceId);
            $stmt->execute();
            
            $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($invoice) {
                // Get invoice items
                $sql = "SELECT sii.*, p.product_name 
                        FROM supplier_invoice_items sii
                        LEFT JOIN products p ON sii.product_id = p.product_id
                        WHERE sii.invoice_id = :invoice_id";
                
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':invoice_id', $invoiceId);
                $stmt->execute();
                
                $invoice['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Get payments
                $sql = "SELECT * FROM supplier_payments 
                        WHERE invoice_id = :invoice_id 
                        ORDER BY payment_date ASC";
                
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':invoice_id', $invoiceId);
                $stmt->execute();
                
                $invoice['payments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            return $invoice;
        } catch(PDOException $e) {
            error_log("Get Supplier Invoice Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all invoices
     * @param string $status
     * @return array|bool
     */
    public function getAll($status = '') {
        try {
            $sql = "SELECT si.*, s.company_name as supplier_name, u.first_name, u.last_name 
                    FROM " . $this->table . " si
                    LEFT JOIN suppliers s ON si.supplier_id = s.supplier_id
                    LEFT JOIN users u ON si.user_id = u.user_id";
            
            if($status) {
                $sql .= " WHERE si.payment_status = :status";
            }
            
            $sql .= " ORDER BY si.invoice_date DESC";
            
            $stmt = $this->db->prepare($sql);
            if($status) {
                $stmt->bindParam(':status', $status);
            }
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Get All Supplier Invoices Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update invoice
     * @param int $invoiceId
     * @param array $data
     * @param array $items
     * @return bool
     */
    public function update($invoiceId, $data, $items) {
        try {
            $this->db->beginTransaction();

            // Check if invoice exists and is unpaid
            $sql = "SELECT payment_status FROM " . $this->table . " WHERE invoice_id = :invoice_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':invoice_id', $invoiceId);
            $stmt->execute();
            
            $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$invoice || $invoice['payment_status'] !== 'unpaid') {
                throw new Exception('Invoice not found or cannot be updated');
            }

            // Update invoice header
            $sql = "UPDATE " . $this->table . " 
                    SET supplier_id = :supplier_id,
                        invoice_number = :invoice_number,
                        invoice_date = :invoice_date,
                        due_date = :due_date,
                        subtotal = :subtotal,
                        tax_amount = :tax_amount,
                        total_amount = :total_amount,
                        payment_due = :total_amount,
                        notes = :notes
                    WHERE invoice_id = :invoice_id";
            
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':invoice_id', $invoiceId);
            $stmt->bindParam(':supplier_id', $data['supplier_id']);
            $stmt->bindParam(':invoice_number', $data['invoice_number']);
            $stmt->bindParam(':invoice_date', $data['invoice_date']);
            $stmt->bindParam(':due_date', $data['due_date']);
            $stmt->bindParam(':subtotal', $data['subtotal']);
            $stmt->bindParam(':tax_amount', $data['tax_amount']);
            $stmt->bindParam(':total_amount', $data['total_amount']);
            $stmt->bindParam(':notes', $data['notes']);
            
            $stmt->execute();

            // Delete existing items
            $sql = "DELETE FROM supplier_invoice_items WHERE invoice_id = :invoice_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':invoice_id', $invoiceId);
            $stmt->execute();

            // Insert new items
            foreach($items as $item) {
                $sql = "INSERT INTO supplier_invoice_items 
                        (invoice_id, product_id, quantity, unit_price, tax_rate, tax_amount, subtotal) 
                        VALUES (:invoice_id, :product_id, :quantity, :unit_price, :tax_rate, :tax_amount, :subtotal)";
                
                $stmt = $this->db->prepare($sql);
                
                $stmt->bindParam(':invoice_id', $invoiceId);
                $stmt->bindParam(':product_id', $item['product_id']);
                $stmt->bindParam(':quantity', $item['quantity']);
                $stmt->bindParam(':unit_price', $item['unit_price']);
                $stmt->bindParam(':tax_rate', $item['tax_rate']);
                $stmt->bindParam(':tax_amount', $item['tax_amount']);
                $stmt->bindParam(':subtotal', $item['subtotal']);
                
                $stmt->execute();

                // Update product cost price if it's different
                $this->updateProductCostPrice($item['product_id'], $item['unit_price']);
            }

            $this->db->commit();
            return true;
        } catch(PDOException $e) {
            $this->db->rollBack();
            error_log("Invoice Update Error: " . $e->getMessage());
            return false;
        } catch(Exception $e) {
            $this->db->rollBack();
            error_log("Invoice Update Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete invoice
     * @param int $invoiceId
     * @return bool
     */
    public function delete($invoiceId) {
        try {
            $this->db->beginTransaction();

            // Check if invoice exists and is unpaid
            $sql = "SELECT payment_status FROM " . $this->table . " WHERE invoice_id = :invoice_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':invoice_id', $invoiceId);
            $stmt->execute();
            
            $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$invoice || $invoice['payment_status'] !== 'unpaid') {
                throw new Exception('Invoice not found or cannot be deleted');
            }

            // Check if there are any payments
            $sql = "SELECT COUNT(*) FROM supplier_payments WHERE invoice_id = :invoice_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':invoice_id', $invoiceId);
            $stmt->execute();
            
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Cannot delete invoice with recorded payments');
            }

            // Delete invoice items first
            $sql = "DELETE FROM supplier_invoice_items WHERE invoice_id = :invoice_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':invoice_id', $invoiceId);
            $stmt->execute();

            // Delete invoice header
            $sql = "DELETE FROM " . $this->table . " WHERE invoice_id = :invoice_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':invoice_id', $invoiceId);
            $stmt->execute();

            $this->db->commit();
            return true;
        } catch(PDOException $e) {
            $this->db->rollBack();
            error_log("Invoice Delete Error: " . $e->getMessage());
            return false;
        } catch(Exception $e) {
            $this->db->rollBack();
            error_log("Invoice Delete Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Record payment
     * @param array $data
     * @return bool
     */
    public function recordPayment($data) {
        try {
            $this->db->beginTransaction();

            // Insert payment record
            $sql = "INSERT INTO supplier_payments 
                    (invoice_id, payment_date, amount, payment_method, reference_number, notes) 
                    VALUES (:invoice_id, :payment_date, :amount, :payment_method, :reference_number, :notes)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($data);

            // Update invoice payment status and due amount
            $sql = "UPDATE " . $this->table . " 
                    SET payment_due = payment_due - :amount,
                        payment_status = CASE 
                            WHEN payment_due - :amount <= 0 THEN 'paid'
                            WHEN payment_due - :amount < total_amount THEN 'partial'
                            ELSE payment_status
                        END
                    WHERE invoice_id = :invoice_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':amount', $data['amount']);
            $stmt->bindParam(':invoice_id', $data['invoice_id']);
            $stmt->execute();

            $this->db->commit();
            return true;
        } catch(PDOException $e) {
            $this->db->rollBack();
            error_log("Record Payment Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if invoice exists
     * @param int $invoiceId
     * @return bool
     */
    public function exists($invoiceId) {
        try {
            $sql = "SELECT COUNT(*) FROM " . $this->table . " WHERE invoice_id = :invoice_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':invoice_id', $invoiceId);
            $stmt->execute();
            
            return $stmt->fetchColumn() > 0;
        } catch(PDOException $e) {
            error_log("Invoice Exists Check Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if invoice is editable
     * @param int $invoiceId
     * @return bool
     */
    public function isEditable($invoiceId) {
        try {
            $sql = "SELECT payment_status FROM " . $this->table . " WHERE invoice_id = :invoice_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':invoice_id', $invoiceId);
            $stmt->execute();
            
            $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
            return $invoice && $invoice['payment_status'] === 'unpaid';
        } catch(PDOException $e) {
            error_log("Invoice Editable Check Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update product cost price
     * @param int $productId
     * @param float $newCostPrice
     * @return bool
     */
    private function updateProductCostPrice($productId, $newCostPrice) {
        try {
            $sql = "UPDATE products 
                    SET cost_price = :cost_price 
                    WHERE product_id = :product_id 
                    AND (cost_price IS NULL OR cost_price != :cost_price)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':product_id', $productId);
            $stmt->bindParam(':cost_price', $newCostPrice);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Update Product Cost Price Error: " . $e->getMessage());
            return false;
        }
    }
}
?>
