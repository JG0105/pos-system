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
$product_data = null;

// Check if ID is provided
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$product = new Product();

// Get product data
try {
    $product_data = $product->getById($_GET['id']);
    if (!$product_data) {
        header("Location: index.php");
        exit();
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $data = [
            'sku' => $_POST['sku'],
            'product_name' => $_POST['product_name'],
            'description' => $_POST['description'] ?? '',
            'unit_price' => $_POST['unit_price'],
            'cost_price' => $_POST['cost_price'] ?? '0.00',
            'stock_level' => (int)$_POST['stock_level'],
            'min_stock_level' => (int)($_POST['min_stock_level'] ?? 0),
            'unit_of_measure' => $_POST['unit_of_measure'],
            'tax_rate' => $_POST['tax_rate'] ?? '10.00',
            'status' => $_POST['status']
        ];

        // Only check SKU exists if it changed
        if ($data['sku'] !== $product_data['sku'] && $product->skuExists($data['sku'])) {
            $error = "SKU already exists";
        } else {
            if ($product->update($_GET['id'], $data)) {
                $success = "Product updated successfully";
                // Refresh product data
                $product_data = $product->getById($_GET['id']);
            } else {
                $error = "Failed to update product";
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
    <title>Edit Product - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Edit Product</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="index.php" class="btn btn-secondary">Back to Products</a>
        </div>
    </div>

    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="sku" class="form-label">SKU *</label>
                            <input type="text" class="form-control" id="sku" name="sku" 
                                   value="<?php echo htmlspecialchars($product_data['sku']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="product_name" class="form-label">Product Name *</label>
                            <input type="text" class="form-control" id="product_name" name="product_name" 
                                   value="<?php echo htmlspecialchars($product_data['product_name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"
                            ><?php echo htmlspecialchars($product_data['description']); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="active" <?php echo $product_data['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $product_data['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="unit_price" class="form-label">Unit Price *</label>
                            <input type="number" step="0.01" class="form-control" id="unit_price" name="unit_price" 
                                   value="<?php echo $product_data['unit_price']; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="cost_price" class="form-label">Cost Price</label>
                            <input type="number" step="0.01" class="form-control" id="cost_price" name="cost_price" 
                                   value="<?php echo $product_data['cost_price']; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="stock_level" class="form-label">Stock Level *</label>
                            <input type="number" class="form-control" id="stock_level" name="stock_level" 
                                   value="<?php echo $product_data['stock_level']; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="min_stock_level" class="form-label">Minimum Stock Level</label>
                            <input type="number" class="form-control" id="min_stock_level" name="min_stock_level" 
                                   value="<?php echo $product_data['min_stock_level']; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="unit_of_measure" class="form-label">Unit of Measure</label>
                            <select class="form-control" id="unit_of_measure" name="unit_of_measure">
                                <option value="piece" <?php echo $product_data['unit_of_measure'] == 'piece' ? 'selected' : ''; ?>>Piece</option>
                                <option value="meter" <?php echo $product_data['unit_of_measure'] == 'meter' ? 'selected' : ''; ?>>Meter</option>
                                <option value="kg" <?php echo $product_data['unit_of_measure'] == 'kg' ? 'selected' : ''; ?>>Kilogram</option>
                                <option value="liter" <?php echo $product_data['unit_of_measure'] == 'liter' ? 'selected' : ''; ?>>Liter</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                            <input type="number" step="0.01" class="form-control" id="tax_rate" name="tax_rate" 
                                   value="<?php echo $product_data['tax_rate']; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">Update Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
