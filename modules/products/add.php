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

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $product = new Product();
        
        // Check if SKU exists
        if ($product->skuExists($_POST['sku'])) {
            $error = "SKU already exists";
        } else {
            $data = [
                'sku' => $_POST['sku'],
                'product_name' => $_POST['product_name'],
                'description' => $_POST['description'] ?? '',
                'unit_price' => $_POST['unit_price'],
                'cost_price' => $_POST['cost_price'] ?? '0.00',
                'stock_level' => (int)$_POST['stock_level'],
                'min_stock_level' => (int)($_POST['min_stock_level'] ?? 0),
                'unit_of_measure' => $_POST['unit_of_measure'],
                'tax_rate' => $_POST['tax_rate'] ?? '10.00'
            ];

            $result = $product->create($data);
            if ($result) {
                header("Location: index.php?success=1");
                exit();
            } else {
                $error = "Failed to create product";
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Add New Product</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="index.php" class="btn btn-secondary">Back to Products</a>
        </div>
    </div>

    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="sku" class="form-label">SKU *</label>
                            <input type="text" class="form-control" id="sku" name="sku" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="product_name" class="form-label">Product Name *</label>
                            <input type="text" class="form-control" id="product_name" name="product_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="unit_price" class="form-label">Unit Price *</label>
                            <input type="number" step="0.01" class="form-control" id="unit_price" name="unit_price" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="cost_price" class="form-label">Cost Price</label>
                            <input type="number" step="0.01" class="form-control" id="cost_price" name="cost_price">
                        </div>
                        
                        <div class="mb-3">
                            <label for="stock_level" class="form-label">Stock Level *</label>
                            <input type="number" class="form-control" id="stock_level" name="stock_level" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="min_stock_level" class="form-label">Minimum Stock Level</label>
                            <input type="number" class="form-control" id="min_stock_level" name="min_stock_level">
                        </div>
                        
                        <div class="mb-3">
                            <label for="unit_of_measure" class="form-label">Unit of Measure</label>
                            <select class="form-control" id="unit_of_measure" name="unit_of_measure">
                                <option value="piece">Piece</option>
                                <option value="meter">Meter</option>
                                <option value="kg">Kilogram</option>
                                <option value="liter">Liter</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                            <input type="number" step="0.01" class="form-control" id="tax_rate" name="tax_rate" value="10.00">
                        </div>
                    </div>
                </div>
                
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">Create Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
