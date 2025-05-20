<?php
session_start();
require_once '../../config/database.php';
require_once '../../classes/SupplierInvoice.php';
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

$invoice_id = (int)$_GET['id'];
$supplierInvoice = new SupplierInvoice();

// Get invoice data
$invoice_data = $supplierInvoice->getById($invoice_id);
if(!$invoice_data || $invoice_data['payment_status'] == 'paid') {
    $_SESSION['status_message'] = 'Invoice not found or already paid';
    header("Location: index.php");
    exit();
}

$error = '';

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if(empty($_POST['amount']) || empty($_POST['payment_method'])) {
            throw new Exception('Please fill in all required fields');
        }

        if($_POST['amount'] <= 0 || $_POST['amount'] > $invoice_data['payment_due']) {
            throw new Exception('Invalid payment amount');
        }

        $payment_data = [
            'invoice_id' => $invoice_id,
            'payment_date' => $_POST['payment_date'],
            'amount' => $_POST['amount'],
            'payment_method' => $_POST['payment_method'],
            'reference_number' => $_POST['reference_number'],
            'notes' => $_POST['notes']
        ];

        if($supplierInvoice->recordPayment($payment_data)) {
            $_SESSION['status_message'] = 'Payment recorded successfully';
            header("Location: view.php?id=" . $invoice_id);
            exit();
        } else {
            throw new Exception('Failed to record payment');
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
            <h1><i class="fas fa-money-bill"></i> Record Payment</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="view.php?id=<?php echo $invoice_id; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Invoice
            </a>
        </div>
    </div>

    <?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Invoice Details</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>Invoice Number:</th>
                            <td><?php echo htmlspecialchars($invoice_data['invoice_number']); ?></td>
                        </tr>
                        <tr>
                            <th>Supplier:</th>
                            <td><?php echo htmlspecialchars($invoice_data['supplier_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Total Amount:</th>
                            <td>$<?php echo number_format($invoice_data['total_amount'], 2); ?></td>
                        </tr>
                        <tr>
                            <th>Amount Due:</th>
                            <td>$<?php echo number_format($invoice_data['payment_due'], 2); ?></td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $invoice_data['payment_status'] == 'paid' ? 'success' : 
                                        ($invoice_data['payment_status'] == 'partial' ? 'warning' : 'danger'); 
                                ?>">
                                    <?php echo ucfirst($invoice_data['payment_status']); ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payment Details</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="payment_date" class="form-label">Payment Date *</label>
                            <input type="date" class="form-control" id="payment_date" name="payment_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       step="0.01" max="<?php echo $invoice_data['payment_due']; ?>" required>
                            </div>
                            <div class="form-text">Maximum amount: $<?php echo number_format($invoice_data['payment_due'], 2); ?></div>
                        </div>

                        <div class="mb-3">
                            <label for="payment_method" class="form-label">Payment Method *</label>
                            <select class="form-select" id="payment_method" name="payment_method" required>
                                <option value="">Select Payment Method</option>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="check">Check</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="reference_number" class="form-label">Reference Number</label>
                            <input type="text" class="form-control" id="reference_number" name="reference_number">
                            <div class="form-text">E.g., Check number, transaction ID, etc.</div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Record Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
})()
</script>

<?php require_once '../../includes/footer.php'; ?>
