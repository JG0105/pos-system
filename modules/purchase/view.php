<?php
session_start();
require_once '../../config/database.php';
require_once '../../classes/Purchase.php';
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

$purchase_id = (int)$_GET['id'];
$purchase = new Purchase();

// Get purchase data with items
$purchase_data = $purchase->getById($purchase_id);
if(!$purchase_data) {
    $_SESSION['status_message'] = 'Purchase order not found';
    header("Location: index.php");
    exit();
}

// Include header
require_once '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-shopping-basket"></i> View Purchase Order</h1>
        </div>
        <div class="col-md-6 text-end">
            <?php if($purchase_data['status'] == 'pending'): ?>
                <a href="receive.php?id=<?php echo $purchase_id; ?>" class="btn btn-success">
                    <i class="fas fa-check"></i> Receive Order
                </a>
            <?php endif; ?>
            <a href="print.php?id=<?php echo $purchase_id; ?>" class="btn btn-secondary" target="_blank">
                <i class="fas fa-print"></i> Print
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Purchases
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Purchase Order Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Purchase Order Details</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <h6 class="mb-3">From:</h6>
                            <div><strong><?php echo htmlspecialchars($purchase_data['supplier_name']); ?></strong></div>
                            <div>Reference: <?php echo htmlspecialchars($purchase_data['reference_no']); ?></div>
                            <div>Date: <?php echo date('d/m/Y', strtotime($purchase_data['purchase_date'])); ?></div>
                        </div>
                        <div class="col-sm-6">
                            <h6 class="mb-3">Status:</h6>
                            <span class="badge bg-<?php 
                                echo $purchase_data['status'] == 'received' ? 'success' : 
                                    ($purchase_data['status'] == 'pending' ? 'warning' : 'danger'); 
                            ?> fs-6">
                                <?php echo ucfirst($purchase_data['status']); ?>
                            </span>
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
                                <?php foreach($purchase_data['items'] as $item): ?>
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
                                    <td class="text-end">$<?php echo number_format($purchase_data['subtotal'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Tax Amount:</strong></td>
                                    <td class="text-end">$<?php echo number_format($purchase_data['tax_amount'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Total Amount:</strong></td>
                                    <td class="text-end"><strong>$<?php echo number_format($purchase_data['total_amount'], 2); ?></strong></td>
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
                    <table class="table table-bordered">
                        <tr>
                            <th>Created By</th>
                            <td><?php echo htmlspecialchars($purchase_data['first_name'] . ' ' . $purchase_data['last_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Created Date</th>
                            <td><?php echo date('d/m/Y H:i', strtotime($purchase_data['created_at'])); ?></td>
                        </tr>
                        <?php if($purchase_data['notes']): ?>
                            <tr>
                                <th>Notes</th>
                                <td><?php echo nl2br(htmlspecialchars($purchase_data['notes'])); ?></td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- Status History -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Status History</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>Created</strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y H:i', strtotime($purchase_data['created_at'])); ?>
                                    </small>
                                </div>
                                <span class="badge bg-info">Created</span>
                            </div>
                        </li>
                        <?php if($purchase_data['status'] != 'pending'): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo ucfirst($purchase_data['status']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo date('d/m/Y H:i', strtotime($purchase_data['updated_at'])); ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-<?php 
                                        echo $purchase_data['status'] == 'received' ? 'success' : 'danger'; 
                                    ?>">
                                        <?php echo ucfirst($purchase_data['status']); ?>
                                    </span>
                                </div>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
