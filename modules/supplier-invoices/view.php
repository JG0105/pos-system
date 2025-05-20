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

// Get invoice data with items and payments
$invoice_data = $supplierInvoice->getById($invoice_id);
if(!$invoice_data) {
    $_SESSION['status_message'] = 'Invoice not found';
    header("Location: index.php");
    exit();
}

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
            <h1><i class="fas fa-file-invoice-dollar"></i> View Invoice</h1>
        </div>
        <div class="col-md-6 text-end">
            <?php if($invoice_data['payment_status'] != 'paid'): ?>
                <a href="payment.php?id=<?php echo $invoice_id; ?>" class="btn btn-success">
                    <i class="fas fa-money-bill"></i> Record Payment
                </a>
            <?php endif; ?>
            <a href="print.php?id=<?php echo $invoice_id; ?>" class="btn btn-secondary" target="_blank">
                <i class="fas fa-print"></i> Print
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Invoices
            </a>
        </div>
    </div>

    <?php if($statusMessage): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $statusMessage; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <!-- Invoice Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Invoice Details</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <h6 class="mb-3">Supplier:</h6>
                            <div><strong><?php echo htmlspecialchars($invoice_data['supplier_name']); ?></strong></div>
                            <div>Invoice #: <?php echo htmlspecialchars($invoice_data['invoice_number']); ?></div>
                            <div>Date: <?php echo date('d/m/Y', strtotime($invoice_data['invoice_date'])); ?></div>
                            <div>Due Date: <?php echo date('d/m/Y', strtotime($invoice_data['due_date'])); ?></div>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <h6 class="mb-3">Status:</h6>
                            <span class="badge bg-<?php 
                                echo $invoice_data['payment_status'] == 'paid' ? 'success' : 
                                    ($invoice_data['payment_status'] == 'partial' ? 'warning' : 'danger'); 
                            ?> fs-6">
                                <?php echo ucfirst($invoice_data['payment_status']); ?>
                            </span>
                            <?php if($invoice_data['payment_status'] != 'paid'): ?>
                                <div class="mt-2">
                                    <strong>Amount Due: $<?php echo number_format($invoice_data['payment_due'], 2); ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Tax</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($invoice_data['items'] as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                                        <td class="text-end">$<?php echo number_format($item['unit_price'], 2); ?></td>
                                        <td class="text-end">$<?php echo number_format($item['tax_amount'], 2); ?></td>
                                        <td class="text-end">$<?php echo number_format($item['subtotal'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                    <td class="text-end">$<?php echo number_format($invoice_data['subtotal'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Tax Amount:</strong></td>
                                    <td class="text-end">$<?php echo number_format($invoice_data['tax_amount'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Total Amount:</strong></td>
                                    <td class="text-end"><strong>$<?php echo number_format($invoice_data['total_amount'], 2); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <?php if($invoice_data['notes']): ?>
                        <div class="mt-4">
                            <h6>Notes:</h6>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($invoice_data['notes'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Payment History -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payment History</h5>
                </div>
                <div class="card-body">
                    <?php if(!empty($invoice_data['payments'])): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Reference</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($invoice_data['payments'] as $payment): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?></td>
                                            <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                            <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                            <td><?php echo htmlspecialchars($payment['reference_number']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="mb-0 text-muted">No payments recorded yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Additional Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Created By:</th>
                            <td><?php echo htmlspecialchars($invoice_data['first_name'] . ' ' . $invoice_data['last_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Created Date:</th>
                            <td><?php echo date('d/m/Y H:i', strtotime($invoice_data['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <th>Last Updated:</th>
                            <td><?php echo date('d/m/Y H:i', strtotime($invoice_data['updated_at'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
