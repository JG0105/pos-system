<?php
session_start();
require_once '../../config/database.php';
require_once '../../classes/Sales.php';
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

$sale_id = (int)$_GET['id'];
$sales = new Sales();

// Get sale data
$sale_data = $sales->getById($sale_id);
if(!$sale_data) {
    $_SESSION['status_message'] = 'Sale not found';
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
            <h1><i class="fas fa-shopping-cart"></i> View Sale</h1>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <button type="button" 
                        class="btn btn-secondary dropdown-toggle" 
                        data-bs-toggle="dropdown" 
                        aria-expanded="false">
                    <i class="fas fa-print"></i> Print Options
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <a class="dropdown-item" href="print.php?id=<?php echo $sale_id; ?>&type=invoice" target="_blank">
                            <i class="fas fa-file-invoice"></i> Tax Invoice
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="print.php?id=<?php echo $sale_id; ?>&type=receipt" target="_blank">
                            <i class="fas fa-receipt"></i> Receipt
                        </a>
                    </li>
                </ul>
            </div>
            <?php if($sale_data['payment_status'] == 'pending'): ?>
                <button type="button" 
                        class="btn btn-success"
                        onclick="markAsPaid(<?php echo $sale_id; ?>)">
                    <i class="fas fa-check"></i> Mark as Paid
                </button>
            <?php endif; ?>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Sales
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
            <!-- Sale Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Sale Details</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <h6 class="mb-3">Customer:</h6>
                            <?php if($sale_data['customer_id']): ?>
                                <div>
                                    <strong>
                                        <?php 
                                        echo $sale_data['company_name'] 
                                            ? htmlspecialchars($sale_data['company_name'])
                                            : htmlspecialchars($sale_data['first_name'] . ' ' . $sale_data['last_name']); 
                                        ?>
                                    </strong>
                                </div>
                            <?php else: ?>
                                <div><strong>Walk-in Customer</strong></div>
                            <?php endif; ?>
                            <div>Invoice #: <?php echo 'INV-' . str_pad($sale_data['sale_id'], 6, '0', STR_PAD_LEFT); ?></div>
                            <div>Date: <?php echo date('d/m/Y', strtotime($sale_data['sale_date'])); ?></div>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <h6 class="mb-3">Status:</h6>
                            <span class="badge bg-<?php 
                                echo $sale_data['payment_status'] == 'paid' ? 'success' : 
                                    ($sale_data['payment_status'] == 'pending' ? 'warning' : 'danger'); 
                            ?> fs-6">
                                <?php echo ucfirst($sale_data['payment_status']); ?>
                            </span>
                            <div class="mt-2">
                                Payment Method: <?php echo ucfirst(str_replace('_', ' ', $sale_data['payment_method'])); ?>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">GST</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($sale_data['items'] as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                                        <td class="text-end">$<?php echo number_format($item['unit_price'], 2); ?></td>
                                        <td class="text-end">$<?php echo number_format($item['tax_amount'], 2); ?></td>
                                        <td class="text-end">$<?php echo number_format($item['total_amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                    <td class="text-end">$<?php echo number_format($sale_data['subtotal'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>GST:</strong></td>
                                    <td class="text-end">$<?php echo number_format($sale_data['tax_amount'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Total Amount:</strong></td>
                                    <td class="text-end"><strong>$<?php echo number_format($sale_data['total_amount'], 2); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Additional Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Additional Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Created By:</th>
                            <td><?php echo htmlspecialchars($sale_data['user_first_name'] . ' ' . $sale_data['user_last_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Created Date:</th>
                            <td><?php echo date('d/m/Y H:i', strtotime($sale_data['created_at'])); ?></td>
                        </tr>
                        <?php if($sale_data['notes']): ?>
                            <tr>
                                <th>Notes:</th>
                                <td><?php echo nl2br(htmlspecialchars($sale_data['notes'])); ?></td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function markAsPaid(saleId) {
    if(confirm('Mark this sale as paid?')) {
        window.location.href = 'mark_paid.php?id=' + saleId;
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>
