<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Create sales table
    $sql = "CREATE TABLE IF NOT EXISTS sales (
        sale_id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT,
        user_id INT NOT NULL,
        sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        payment_method ENUM('cash', 'card', 'direct_deposit') NOT NULL,
        payment_status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
        FOREIGN KEY (user_id) REFERENCES users(user_id)
    )";
    
    $db->exec($sql);
    echo "Sales table created successfully!<br>";
    
    // Create sale_items table
    $sql = "CREATE TABLE IF NOT EXISTS sale_items (
        item_id INT AUTO_INCREMENT PRIMARY KEY,
        sale_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        unit_price DECIMAL(10,2) NOT NULL,
        tax_rate DECIMAL(5,2) NOT NULL DEFAULT 10.00,
        tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        FOREIGN KEY (sale_id) REFERENCES sales(sale_id),
        FOREIGN KEY (product_id) REFERENCES products(product_id)
    )";
    
    $db->exec($sql);
    echo "Sale items table created successfully!<br>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
