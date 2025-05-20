<?php
session_start();
require_once '../../config/database.php';
require_once '../../classes/SupplierInvoice.php';
require_once '../../classes/Supplier.php';
require_once '../../classes/Product.php';
require_once '../../includes/functions.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Initialize classes
$supplierInvoice = new SupplierInvoice();
$supplier = new Supplier();
$product = new Product();

// Get all active suppliers
$suppliers = $supplier->getAll('active');
// Get all active products
$products = $product->getAll('active');

$error = '';
$success = '';

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate basic inputs
        if(empty($_POST['supplier_id']) || empty($_POST['items'])) {
            throw new Exception('Please select a supplier and add at least one item');
        }

        // Prepare invoice data
        $invoiceData = [
            'supplier_id' => $_POST['supplier_id'],
            'user_id' => $_SESSION['user_id'],
            'invoice_number' => $_POST['invoice_number'],
            'invoice_date' => $_POST['invoice_date'],
            'due_date' => $_POST['due_date'],
            'subtotal' => $_POST['subtotal'],
            'tax_amount' => $_POST['tax_amount'],
            'total_amount' => $_POST['total_amount'],
            'payment_status' => 'unpaid',
            'notes' => $_POST['notes']
        ];

        // Create invoice with items
        if($supplierInvoice->create($invoiceData, $_POST['items'])) {
            $_SESSION['status_message'] = 'Supplier invoice created successfully';
            header("Location: index.php");
            exit();
        } else {
            throw new Exception('Failed to create supplier invoice');
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
            <h1><i class="fas fa-file-invoice-dollar"></i> New Supplier Invoice</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Invoices
            </a>
        </div>
    </div>

    <?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <form id="invoiceForm" method="POST" action="" class="needs-validation" novalidate>
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="supplier_id" class="form-label">Supplier *</label>
                                <select class="form-select" id="supplier_id" name="supplier_id" required>
                                    <option value="">Select Supplier</option>
                                    <?php foreach($suppliers as $sup): ?>
                                        <option value="<?php echo $sup['supplier_id']; ?>">
                                            <?php echo htmlspecialchars($sup['company_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="invoice_number" class="form-label">Invoice Number *</label>
                                <input type="text" class="form-control" id="invoice_number" name="invoice_number" required>
                            </div>
                            <div class="col-md-4">
                                <label for="invoice_date" class="form-label">Invoice Date *</label>
                                <input type="date" class="form-control" id="invoice_date" name="invoice_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="due_date" class="form-label">Due Date *</label>
                                <input type="date" class="form-control" id="due_date" name="due_date" 
                                       value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
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
                            <label class="form-label">Tax Amount</label>
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
                            <i class="fas fa-save"></i> Save Invoice
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
                            data-price="<?php echo $prod['cost_price'] ?: $prod['unit_price']; ?>"
                            data-tax="<?php echo $prod['tax_rate']; ?>">
                        <?php echo htmlspecialchars($prod['product_name']); ?>
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
                   step="0.01" required>
        </td>
        <td>
            <input type="number" class="form-control tax-rate" name="items[{index}][tax_rate]" 
                   step="0.01" required>
        </td>
        <td>
            <input type="text" class="form-control subtotal" name="items[{index}][subtotal]" readonly>
            <input type="hidden" class="tax-amount" name="items[{index}][tax_amount]">
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
    const form = document.getElementById('invoiceForm');
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
            unitPriceInput.value = option.dataset.price || '';
            taxRateInput.value = option.dataset.tax || '';
            calculateRowTotal(row);
        });

        // Quantity or price change
        [quantityInput, unitPriceInput, taxRateInput].forEach(input => {
            input.addEventListener('input', function() {
                calculateRowTotal(row);
            });
        });
    }

    function calculateRowTotal(row) {
        const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
        const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
        const taxRate = parseFloat(row.querySelector('.tax-rate').value) || 0;
        
        const subtotal = quantity * unitPrice;
        const taxAmount = subtotal * (taxRate / 100);
        
        row.querySelector('.subtotal').value = subtotal.toFixed(2);
        row.querySelector('.tax-amount').value = taxAmount.toFixed(2);
        
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
