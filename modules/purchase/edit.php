<?php
session_start();
require_once '../../config/database.php';
require_once '../../classes/Purchase.php';
require_once '../../classes/Supplier.php';
require_once '../../classes/Product.php';
require_once '../../includes/functions.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Check if ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['status_message'] = 'Invalid purchase ID';
    header("Location: index.php");
    exit();
}

// Initialize classes
$purchase = new Purchase();
$supplier = new Supplier();
$product = new Product();

$purchase_id = (int)$_GET['id'];

// Get purchase data
$purchase_data = $purchase->getById($purchase_id);
if(!$purchase_data || $purchase_data['status'] != 'pending') {
    $_SESSION['status_message'] = 'Purchase order not found or cannot be edited';
    header("Location: index.php");
    exit();
}

// Get all active suppliers
$suppliers = $supplier->getAll('active');
// Get all active products
$products = $product->getAll('active');

$error = '';

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate basic inputs
        if(empty($_POST['supplier_id']) || empty($_POST['items'])) {
            throw new Exception('Please select a supplier and add at least one item');
        }

        // Prepare purchase data
        $purchaseData = [
            'supplier_id' => $_POST['supplier_id'],
            'subtotal' => $_POST['subtotal'],
            'tax_amount' => $_POST['tax_amount'],
            'total_amount' => $_POST['total_amount'],
            'notes' => $_POST['notes']
        ];

        // Update purchase with items
        if($purchase->update($purchase_id, $purchaseData, $_POST['items'])) {
            $_SESSION['status_message'] = 'Purchase order updated successfully';
            header("Location: index.php");
            exit();
        } else {
            throw new Exception('Failed to update purchase order');
        }
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}

// Include header
require_once '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-shopping-basket"></i> Edit Purchase Order</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Purchases
            </a>
        </div>
    </div>

    <?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <form id="purchaseForm" method="POST" action="">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="supplier_id" class="form-label">Supplier *</label>
                                <select class="form-select" id="supplier_id" name="supplier_id" required>
                                    <option value="">Select Supplier</option>
                                    <?php foreach($suppliers as $sup): ?>
                                        <option value="<?php echo $sup['supplier_id']; ?>" 
                                                <?php echo ($sup['supplier_id'] == $purchase_data['supplier_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($sup['company_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="reference_no" class="form-label">Reference No</label>
                                <input type="text" class="form-control" id="reference_no" 
                                       value="<?php echo htmlspecialchars($purchase_data['reference_no']); ?>" readonly>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th width="100">Quantity</th>
                                        <th width="150">Unit Price</th>
                                        <th width="120">Tax (%)</th>
                                        <th width="150">Subtotal</th>
                                        <th width="50">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($purchase_data['items'] as $index => $item): ?>
                                        <tr>
                                            <td>
                                                <select class="form-select product-select" name="items[<?php echo $index; ?>][product_id]" required>
                                                    <option value="">Select Product</option>
                                                    <?php foreach($products as $prod): ?>
                                                        <option value="<?php echo $prod['product_id']; ?>" 
                                                                data-price="<?php echo $prod['unit_price']; ?>"
                                                                data-tax="<?php echo $prod['tax_rate']; ?>"
                                                                <?php echo ($prod['product_id'] == $item['product_id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($prod['product_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control quantity" 
                                                       name="items[<?php echo $index; ?>][quantity]" 
                                                       value="<?php echo $item['quantity']; ?>" min="1" required>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control unit-price" 
                                                       name="items[<?php echo $index; ?>][unit_price]" 
                                                       value="<?php echo $item['unit_price']; ?>" step="0.01" required>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control tax-rate" 
                                                       name="items[<?php echo $index; ?>][tax_rate]" 
                                                       value="<?php echo ($item['tax_amount'] / $item['subtotal']) * 100; ?>" 
                                                       step="0.01" required>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control subtotal" 
                                                       name="items[<?php echo $index; ?>][subtotal]" 
                                                       value="<?php echo $item['subtotal']; ?>" readonly>
                                                <input type="hidden" class="tax-amount" 
                                                       name="items[<?php echo $index; ?>][tax_amount]" 
                                                       value="<?php echo $item['tax_amount']; ?>">
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-sm remove-item">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="6">
                                            <button type="button" class="btn btn-success btn-sm" id="addItem">
                                                <i class="fas fa-plus"></i> Add Item
                                            </button>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($purchase_data['notes']); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Subtotal</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" class="form-control" id="subtotal" name="subtotal" 
                                       value="<?php echo $purchase_data['subtotal']; ?>" readonly>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tax Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" class="form-control" id="tax_amount" name="tax_amount" 
                                       value="<?php echo $purchase_data['tax_amount']; ?>" readonly>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Total Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" class="form-control" id="total_amount" name="total_amount" 
                                       value="<?php echo $purchase_data['total_amount']; ?>" readonly>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save"></i> Update Purchase Order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Product selection template -->
<template id="productRowTemplate">
    <!-- Same template as in add.php -->
</template>

<script>
// Same JavaScript as in add.php with minor modifications for edit functionality
</script>

<?php require_once '../../includes/footer.php'; ?>
