<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Setting up POS Database Tables</h1>";

try {
    $host = 'localhost';
    $dbname = 'dbr4ccrunbea1c';
    $username = 'u8pspp1ggfdgc';
    $password = 't7u434m97jzi';

    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname",
        $username,
        $password,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );

    // Create categories table
    $sql_categories = "CREATE TABLE IF NOT EXISTS categories (
        category_id INT AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(50) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql_categories);
    echo "<p style='color: green'>Categories table created successfully</p>";

    // Create products table
    $sql_products = "CREATE TABLE IF NOT EXISTS products (
        product_id INT AUTO_INCREMENT PRIMARY KEY,
        sku VARCHAR(50) UNIQUE NOT NULL,
        barcode VARCHAR(50),
        product_name VARCHAR(100) NOT NULL,
        description TEXT,
        category_id INT,
        unit_price DECIMAL(10,2) NOT NULL,
        cost_price DECIMAL(10,2),
        stock_level INT DEFAULT 0,
        min_stock_level INT DEFAULT 0,
        unit_of_measure VARCHAR(20),
        tax_rate DECIMAL(5,2) DEFAULT 10.00,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(category_id)
    )";
    $pdo->exec($sql_products);
    echo "<p style='color: green'>Products table created successfully</p>";

    // Create customers table
    $sql_customers = "CREATE TABLE IF NOT EXISTS customers (
        customer_id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(100) UNIQUE,
        phone VARCHAR(20),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql_customers);
    echo "<p style='color: green'>Customers table created successfully</p>";

    // Create users table
    $sql_users = "CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(50),
        last_name VARCHAR(50),
        role ENUM('admin', 'manager', 'cashier') NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql_users);
    echo "<p style='color: green'>Users table created successfully</p>";

    // Create sales table
    $sql_sales = "CREATE TABLE IF NOT EXISTS sales (
        sale_id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT,
        user_id INT NOT NULL,
        sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        subtotal DECIMAL(10,2) NOT NULL,
        tax_amount DECIMAL(10,2) NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        payment_method ENUM('cash', 'card', 'other') NOT NULL,
        status ENUM('completed', 'cancelled', 'refunded') DEFAULT 'completed',
        notes TEXT,
        FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
        FOREIGN KEY (user_id) REFERENCES users(user_id)
    )";
    $pdo->exec($sql_sales);
    echo "<p style='color: green'>Sales table created successfully</p>";

    // Create sale_items table
    $sql_sale_items = "CREATE TABLE IF NOT EXISTS sale_items (
        sale_item_id INT AUTO_INCREMENT PRIMARY KEY,
        sale_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        tax_amount DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (sale_id) REFERENCES sales(sale_id),
        FOREIGN KEY (product_id) REFERENCES products(product_id)
    )";
    $pdo->exec($sql_sale_items);
    echo "<p style='color: green'>Sale Items table created successfully</p>";

    echo "<h2 style='color: green'>All tables created successfully!</h2>";

} catch(PDOException $e) {
    die("<p style='color: red'>Setup failed: " . $e->getMessage() . "</p>");
}
?>
