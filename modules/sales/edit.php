<?php
session_start();
require_once '../../config/database.php';
require_once '../../classes/Sales.php';
require_once '../../classes/Customer.php';
require_once '../../classes/Product.php';
require_once '../../includes/functions.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Check if ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['status_message'] = 'Invalid sale ID';
    header("Location: index.php");
    exit();
}

// Initialize classes
$sales = new Sales();
$customer = new Customer();
$product = new Product();

$sale_id = (int)$_GET['id'];

// Get sale data
$sale_data = $sales->getById($sale_id);
if(!$sale_data || $sale_data['payment_status'] != 'pending') {
    $_SESSION['status_message'] = 'Sale not found or cannot be edited';
    header("Location: index.php");
    exit();
}

// Get all active customers
$customers = $customer->getAll('active');
// Get all active products
$products = $product->getAll('active');

$error = '';

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate basic inputs
        if(empty($_POST['items'])) {
            throw new Exception('Please add at least one item');
        }

        // Prepare sale data
        $saleData = [
            'customer_id' => !empty($_POST['customer_id']) ? $_POST['customer_id'] : null,
            'subtotal' => $_POST['subtotal'],
            'tax_amount' => $_POST['tax_amount'],
            'total_amount' => $_POST['total_amount'],
            'payment_method' => $_POST['payment_method'],
            'notes' => $_POST['notes']
        ];

        // Update sale
        if($sales->update($sale_id, $saleData, $_POST['items'])) {
            $_SESSION['status_message'] = 'Sale updated successfully';
            header("Location: index.php");
            exit();
        } else {
            throw new Exception('Failed to update sale');
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
            <h1><i class="fas fa-shopping-cart"></i> Edit Sale</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Sales
            </a>
        </div>
    </div>

    <?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <form id="saleForm" method="POST" action="" class="needs-validation" novalidate>
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="customer_id" class="form-label">Customer</label>
                                <select class="form-select" id="customer_id" name="customer_id">
                                    <option value="">Walk-in Customer</option>
                                    <?php foreach($customers as $cust): ?>
                                        <option value="<?php echo $cust['customer_id']; ?>"
                                                <?php echo ($cust['customer_id'] == $sale_data['customer_id']) ? 'selected' : ''; ?>>
                                            <?php 
                                            echo $cust['company_name'] 
                                                ? htmlspecialchars($cust['company_name'])
                                                : htmlspecialchars($cust['first_name'] . ' ' . $cust['last_name']); 
                                            ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="payment_method" class="form-label">Payment Method *</label>
                                <select class="form-select" id="payment_method" name="payment_method" required>
                                    <option value="cash" <?php echo $sale_data['payment_method'] == 'cash' ? 'selected' : ''; ?>>Cash</option>
                                    <option value="card" <?php echo $sale_data['payment_method'] == 'card' ? 'selected' : ''; ?>>Card</option>
                                    <option value="direct_deposit" <?php echo $sale_data['payment_method'] == 'direct_deposit' ? 'selected' : ''; ?>>Direct Deposit</option>
                                </select>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th width="100">Quantity</th>
                                        <th width="150">Unit Price</th>
                                        <th width="120">GST</th>
                                        <th width="150">Total</th>
                                        <th width="50">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($sale_data['items'] as $index => $item): ?>
                                        <tr>
                                            <td>
                                                <select class="form-select product-select" 
                                                        name="items[<?php echo $index; ?>][product_id]" required>
                                                    <option value="">Select Product</option>
                                                    <?php foreach($products as $prod): ?>
                                                        <option value="<?php echo $prod['product_id']; ?>"
                                                                data-price="<?php echo $prod['unit_price']; ?>"
                                                                data-tax="<?php echo $prod['tax_rate']; ?>"
                                                                data-stock="<?php echo $prod['stock_level']; ?>"
                                                                <?php echo ($prod['product_id'] == $item['product_id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($prod['product_name']); ?>
                                                            (Stock: <?php echo $prod['stock_level']; ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control quantity" 
                                                       name="items[<?php echo $index; ?>][quantity]"
                                                       value="<?php echo $item['quantity']; ?>"
                                                       min="1" required>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control unit-price" 
                                                       name="items[<?php echo $index; ?>][unit_price]"
                                                       value="<?php echo $item['unit_price']; ?>"
                                                       step="0.01" required readonly>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control tax-rate" 
                                                       name="items[<?php echo $index; ?>][tax_rate]"
                                                       value="<?php echo ($item['tax_amount'] / $item['subtotal']) * 100; ?>"
                                                       step="0.01" required readonly>
                                                <input type="hidden" class="tax-amount" 
                                                       name="items[<?php echo $index; ?>][tax_amount]"
                                                       value="<?php echo $item['tax_amount']; ?>">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control total-amount" 
                                                       name="items[<?php echo $index; ?>][total_amount]"
                                                       value="<?php echo $item['total_amount']; ?>" readonly>
                                                <input type="hidden" class="subtotal" 
                                                       name="items[<?php echo $index; ?>][subtotal]"
                                                       value="<?php echo $item['subtotal']; ?>">
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
                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($sale_data['notes']); ?></textarea>
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
                                       value="<?php echo $sale_data['subtotal']; ?>" readonly>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">GST</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" class="form-control" id="tax_amount" name="tax_amount" 
                                       value="<?php echo $sale_data['tax_amount']; ?>" readonly>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Total Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" class="form-control" id="total_amount" name="total_amount" 
                                       value="<?php echo $sale_data['total_amount']; ?>" readonly>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save"></i> Update Sale
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
// Same JavaScript as in add.php with initial rowIndex set to current number of items
document.addEventListener('DOMContentLoaded', function() {
    let rowIndex = <?php echo count($sale_data['items']); ?>;
    // Rest of the JavaScript remains the same as add.php
});
</script>

<?php require_once '../../includes/footer.php'; ?>
