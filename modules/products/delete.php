<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/database.php';
require_once '../../classes/Product.php';

// Check if ID is provided
if (!isset($_GET['id'])) {
    header("Location: index.php?error=no_id");
    exit();
}

try {
    $product = new Product();
    
    // Get product details before deletion (for confirmation message)
    $product_data = $product->getById($_GET['id']);
    
    if (!$product_data) {
        header("Location: index.php?error=not_found");
        exit();
    }

    // Confirm deletion with form submission
    if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
        if ($product->delete($_GET['id'])) {
            header("Location: index.php?success=3&deleted=" . urlencode($product_data['product_name']));
            exit();
        } else {
            throw new Exception("Failed to delete product");
        }
    }
} catch (Exception $e) {
    header("Location: index.php?error=" . urlencode($e->getMessage()));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Product - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Delete Product</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="index.php" class="btn btn-secondary">Back to Products</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Confirm Deletion</h5>
            <p class="card-text">Are you sure you want to delete the following product?</p>
            
            <div class="alert alert-warning">
                <strong>Product Details:</strong><br>
                SKU: <?php echo htmlspecialchars($product_data['sku']); ?><br>
                Name: <?php echo htmlspecialchars($product_data['product_name']); ?><br>
                Current Stock: <?php echo $product_data['stock_level']; ?> <?php echo htmlspecialchars($product_data['unit_of_measure']); ?><br>
                Price: $<?php echo number_format($product_data['unit_price'], 2); ?>
            </div>

            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> Warning: This action cannot be undone!
            </div>

            <form method="POST" action="">
                <input type="hidden" name="confirm" value="yes">
                <div class="text-end">
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-danger">Delete Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
