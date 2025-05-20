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

// Initialize classes
$sales = new Sales();
$customer = new Customer();
$product = new Product();

// Get all active customers
$customers = $customer->getAll('active');
// Get all active products
$products = $product->getAll('active');

$error = '';
$success = '';

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
            'user_id' => $_SESSION['user_id'],
            'subtotal' => $_POST['subtotal'],
            'tax_amount' => $_POST['tax_amount'],
            'total_amount' => $_POST['total_amount'],
            'payment_method' => $_POST['payment_method'],
            'payment_status' => $_POST['payment_method'] == 'direct_deposit' ? 'pending' : 'paid',
            'notes' => $_POST['notes']
        ];

        // Create sale with items
        if($sales->create($saleData, $_POST['items'])) {
            $_SESSION['status_message'] = 'Sale created successfully';
            header("Location: index.php");
            exit();
        } else {
            throw new Exception('Failed to create sale');
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
            <h1><i class="fas fa-shopping-cart"></i> New Sale</h1>
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
                                        <option value="<?php echo $cust['customer_id']; ?>">
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
                                    <option value="cash">Cash</option>
                                    <option value="card">Card</option>
                                    <option value="direct_deposit">Direct Deposit</option>
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
                                    <!-- Items will be added here dynamically -->
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
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
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
                                <input type="text" class="form-control" id="subtotal" name="subtotal" readonly>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">GST</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" class="form-control" id="tax_amount" name="tax_amount" readonly>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Total Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" class="form-control" id="total_amount" name="total_amount" readonly>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save"></i> Complete Sale
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Product selection template -->
<template id="productRowTemplate">
    <tr>
        <td>
            <select class="form-select product-select" name="items[{index}][product_id]" required>
                <option value="">Select Product</option>
                <?php foreach($products as $prod): ?>
                    <option value="<?php echo $prod['product_id']; ?>" 
                            data-price="<?php echo $prod['unit_price']; ?>"
                            data-tax="<?php echo $prod['tax_rate']; ?>"
                            data-stock="<?php echo $prod['stock_level']; ?>">
                        <?php echo htmlspecialchars($prod['product_name']); ?>
                        (Stock: <?php echo $prod['stock_level']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <input type="number" class="form-control quantity" name="items[{index}][quantity]" 
                   min="1" value="1" required>
        </td>
        <td>
            <input type="number" class="form-control unit-price" name="items[{index}][unit_price]" 
                   step="0.01" required readonly>
        </td>
        <td>
            <input type="number" class="form-control tax-rate" name="items[{index}][tax_rate]" 
                   step="0.01" required readonly>
            <input type="hidden" class="tax-amount" name="items[{index}][tax_amount]">
        </td>
        <td>
            <input type="text" class="form-control total-amount" name="items[{index}][total_amount]" readonly>
            <input type="hidden" class="subtotal" name="items[{index}][subtotal]">
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-sm remove-item">
                <i class="fas fa-times"></i>
            </button>
        </td>
    </tr>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let rowIndex = 0;
    const form = document.getElementById('saleForm');
    const addItemBtn = document.getElementById('addItem');
    const itemsTable = document.getElementById('itemsTable').getElementsByTagName('tbody')[0];
    const template = document.getElementById('productRowTemplate');

    // Add first row by default
    addRow();

    // Add new row
    addItemBtn.addEventListener('click', addRow);

    function addRow() {
        const newRow = template.content.cloneNode(true);
        const row = newRow.querySelector('tr');
        
        // Replace {index} placeholder with actual index
        row.innerHTML = row.innerHTML.replace(/{index}/g, rowIndex++);
        
        // Add event listeners to new row
        addRowListeners(row);
        
        itemsTable.appendChild(row);
    }

    function addRowListeners(row) {
        const removeBtn = row.querySelector('.remove-item');
        const productSelect = row.querySelector('.product-select');
        const quantityInput = row.querySelector('.quantity');
        const unitPriceInput = row.querySelector('.unit-price');
        const taxRateInput = row.querySelector('.tax-rate');

        // Remove row
        removeBtn.addEventListener('click', function() {
            if(itemsTable.rows.length > 1) {
                row.remove();
                calculateTotals();
            }
        });

        // Product selection change
        productSelect.addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            if(option.value) {
                const maxStock = parseInt(option.dataset.stock);
                quantityInput.max = maxStock;
                unitPriceInput.value = option.dataset.price;
                taxRateInput.value = option.dataset.tax;
                calculateRowTotal(row);
            }
        });

        // Quantity change
        quantityInput.addEventListener('input', function() {
            const option = productSelect.options[productSelect.selectedIndex];
            const maxStock = parseInt(option.dataset.stock);
            if(this.value > maxStock) {
                alert('Maximum available stock: ' + maxStock);
                this.value = maxStock;
            }
            calculateRowTotal(row);
        });
    }

    function calculateRowTotal(row) {
        const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
        const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
        const taxRate = parseFloat(row.querySelector('.tax-rate').value) || 0;
        
        const subtotal = quantity * unitPrice;
        const taxAmount = subtotal * (taxRate / 100);
        const total = subtotal + taxAmount;
        
        row.querySelector('.subtotal').value = subtotal.toFixed(2);
        row.querySelector('.tax-amount').value = taxAmount.toFixed(2);
        row.querySelector('.total-amount').value = total.toFixed(2);
        
        calculateTotals();
    }

    function calculateTotals() {
        let subtotal = 0;
        let taxTotal = 0;
        
        itemsTable.querySelectorAll('tr').forEach(row => {
            subtotal += parseFloat(row.querySelector('.subtotal').value) || 0;
            taxTotal += parseFloat(row.querySelector('.tax-amount').value) || 0;
        });
        
        const total = subtotal + taxTotal;
        
        document.getElementById('subtotal').value = subtotal.toFixed(2);
        document.getElementById('tax_amount').value = taxTotal.toFixed(2);
        document.getElementById('total_amount').value = total.toFixed(2);
    }

    // Form validation
    form.addEventListener('submit', function(e) {
        if(!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>
