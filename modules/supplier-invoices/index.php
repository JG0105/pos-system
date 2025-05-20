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

// Initialize SupplierInvoice class
$supplierInvoice = new SupplierInvoice();

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Get all invoices
$invoices = $supplierInvoice->getAll($status);

// Handle status messages
$statusMessage = '';
if(isset($_SESSION['status_message'])) {
    $statusMessage = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

// Include header
require_once '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-file-invoice-dollar"></i> Supplier Invoices</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Invoice
            </a>
        </div>
    </div>

    <?php if($statusMessage): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $statusMessage; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Payment Status</label>
                    <select class="form-select" id="status" name="status" onchange="this.form.submit()">
                        <option value="">All Invoices</option>
                        <option value="unpaid" <?php echo $status == 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                        <option value="partial" <?php echo $status == 'partial' ? 'selected' : ''; ?>>Partially Paid</option>
                        <option value="paid" <?php echo $status == 'paid' ? 'selected' : ''; ?>>Paid</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Supplier</th>
                            <th>Date</th>
                            <th>Due Date</th>
                            <th class="text-end">Total Amount</th>
                            <th class="text-end">Amount Due</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($invoices): ?>
                            <?php foreach($invoices as $invoice): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                                    <td><?php echo htmlspecialchars($invoice['supplier_name']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($invoice['invoice_date'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($invoice['due_date'])); ?></td>
                                    <td class="text-end">$<?php echo number_format($invoice['total_amount'], 2); ?></td>
                                    <td class="text-end">$<?php echo number_format($invoice['payment_due'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $invoice['payment_status'] == 'paid' ? 'success' : 
                                                ($invoice['payment_status'] == 'partial' ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php echo ucfirst($invoice['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view.php?id=<?php echo $invoice['invoice_id']; ?>" 
                                           class="btn btn-sm btn-info text-white" 
                                           title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if($invoice['payment_status'] == 'unpaid'): ?>
                                            <a href="edit.php?id=<?php echo $invoice['invoice_id']; ?>" 
                                               class="btn btn-sm btn-primary" 
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger" 
                                                    title="Delete"
                                                    onclick="confirmDelete(<?php echo $invoice['invoice_id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if($invoice['payment_status'] != 'paid'): ?>
                                            <a href="payment.php?id=<?php echo $invoice['invoice_id']; ?>" 
                                               class="btn btn-sm btn-success" 
                                               title="Record Payment">
                                                <i class="fas fa-money-bill"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="print.php?id=<?php echo $invoice['invoice_id']; ?>" 
                                           class="btn btn-sm btn-secondary" 
                                           title="Print"
                                           target="_blank">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No invoices found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(invoiceId) {
    if(confirm('Are you sure you want to delete this invoice? This action cannot be undone.')) {
        window.location.href = 'delete.php?id=' + invoiceId;
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>
