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

// Check if ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['status_message'] = 'Invalid invoice ID';
    header("Location: index.php");
    exit();
}

// Initialize classes
$supplierInvoice = new SupplierInvoice();
$supplier = new Supplier();
$product = new Product();

$invoice_id = (int)$_GET['id'];

// Get invoice data
$invoice_data = $supplierInvoice->getById($invoice_id);
if(!$invoice_data || $invoice_data['payment_status'] != 'unpaid') {
    $_SESSION['status_message'] = 'Invoice not found or cannot be edited';
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

        // Prepare invoice data
        $invoiceData = [
            'supplier_id' => $_POST['supplier_id'],
            'invoice_number' => $_POST['invoice_number'],
            'invoice_date' => $_POST['invoice_date'],
            'due_date' => $_POST['due_date'],
            'subtotal' => $_POST['subtotal'],
            'tax_amount' => $_POST['tax_amount'],
            'total_amount' => $_POST['total_amount'],
            'notes' => $_POST['notes']
        ];

        // Update invoice
        if($supplierInvoice->update($invoice_id, $invoiceData, $_POST['items'])) {
            $_SESSION['status_message'] = 'Invoice updated successfully';
            header("Location: index.php");
            exit();
        } else {
            throw new Exception('Failed to update invoice');
        }
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}

// Include header
require_once '../../includes/header.php';
?>

<!-- [Previous HTML form structure remains the same as add.php, just pre-populate with $invoice_data values] -->
<!-- Key differences in the form: -->

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-file-invoice-dollar"></i> Edit Invoice</h1>
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
        <!-- Add your form fields here, similar to add.php but with pre-populated values -->
        <!-- Example of pre-populated fields: -->
        <div class="row">
            <div class="col-md-4">
                <label for="supplier_id" class="form-label">Supplier *</label>
                <select class="form-select" id="supplier_id" name="supplier_id" required>
                    <option value="">Select Supplier</option>
                    <?php foreach($suppliers as $sup): ?>
                        <option value="<?php echo $sup['supplier_id']; ?>" 
                                <?php echo ($sup['supplier_id'] == $invoice_data['supplier_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sup['company_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="invoice_number" class="form-label">Invoice Number *</label>
                <input type="text" class="form-control" id="invoice_number" name="invoice_number" 
                       value="<?php echo htmlspecialchars($invoice_data['invoice_number']); ?>" required>
            </div>
            <!-- Add other fields similarly -->
        </div>
        <!-- Rest of the form structure -->
    </form>
</div>

