<?php
session_start();
date_default_timezone_set('Australia/Adelaide');
require_once '../../../config/database.php';
require_once '../../../classes/Reports.php';
require_once '../../../includes/functions.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../../login.php");
    exit();
}

$reports = new Reports();

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : 'all'; // all, pending, partial, received, cancelled
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'date'; // date, supplier, amount, status

// Get suppliers for filter
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT supplier_id, company_name FROM suppliers ORDER BY company_name");
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get report data
$po_data = $reports->getPurchaseOrderStatusReport($start_date, $end_date, $supplier_id, $status, $sort_by);

// Include header
require_once '../../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-clipboard-check"></i> Purchase Order Status</h1>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-primary" onclick="window.print();">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="purchase_order_status_pdf.php?<?php echo http_build_query($_GET); ?>" 
                   class="btn btn-danger" target="_blank">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
                <a href="purchase_order_status_excel.php?<?php echo http_build_query($_GET); ?>" 
                   class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
                <button type="button" class="btn btn-info" onclick="sendReminders();">
                    <i class="fas fa-envelope"></i> Send Reminders
                </button>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-2">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-3">
                    <label for="supplier_id" class="form-label">Supplier</label>
                    <select class="form-select" id="supplier_id" name="supplier_id">
                        <option value="0">All Suppliers</option>
                        <?php foreach($suppliers as $supplier): ?>
                            <option value="<?php echo $supplier['supplier_id']; ?>" 
                                    <?php echo $supplier_id == $supplier['supplier_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($supplier['company_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="partial" <?php echo $status == 'partial' ? 'selected' : ''; ?>>Partially Received</option>
                        <option value="received" <?php echo $status == 'received' ? 'selected' : ''; ?>>Received</option>
                        <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="sort_by" class="form-label">Sort By</label>
                    <select class="form-select" id="sort_by" name="sort_by">
                        <option value="date" <?php echo $sort_by == 'date' ? 'selected' : ''; ?>>Date</option>
                        <option value="supplier" <?php echo $sort_by == 'supplier' ? 'selected' : ''; ?>>Supplier</option>
                        <option value="amount" <?php echo $sort_by == 'amount' ? 'selected' : ''; ?>>Amount</option>
                        <option value="status" <?php echo $sort_by == 'status' ? 'selected' : ''; ?>>Status</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label"> </label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Orders</h5>
                    <h3><?php echo number_format($po_data['total_orders']); ?></h3>
                    <small>$<?php echo number_format($po_data['total_amount'], 2); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Pending Orders</h5>
                    <h3><?php echo number_format($po_data['pending_orders']); ?></h3>
                    <small>$<?php echo number_format($po_data['pending_amount'], 2); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Received Orders</h5>
                    <h3><?php echo number_format($po_data['received_orders']); ?></h3>
                    <small>$<?php echo number_format($po_data['received_amount'], 2); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Average Delivery Time</h5>
                    <h3><?php echo number_format($po_data['avg_delivery_days'], 1); ?></h3>
                    <small>Days</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Charts -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Order Status Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Monthly Order Trend</h5>
                </div>
                <div class="card-body">
                    <canvas id="trendChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Purchase Orders Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Purchase Orders</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th>PO #</th>
                            <th>Date</th>
                            <th>Supplier</th>
                            <th>Expected Date</th>
                            <th class="text-end">Amount</th>
                            <th class="text-center">Items</th>
                            <th class="text-center">Received</th>
                            <th>Status</th>
                            <th>Days Open</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($po_data['orders'] as $order): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input order-select" 
                                           value="<?php echo $order['purchase_id']; ?>">
                                </td>
                                <td><?php echo 'PO-' . str_pad($order['purchase_id'], 6, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($order['purchase_date'])); ?></td>
                                <td><?php echo htmlspecialchars($order['supplier_name']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($order['expected_date'])); ?></td>
                                <td class="text-end">$<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td class="text-center"><?php echo $order['total_items']; ?></td>
                                <td class="text-center"><?php echo $order['received_items']; ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $order['status'] == 'received' ? 'success' : 
                                            ($order['status'] == 'pending' ? 'warning' : 
                                                ($order['status'] == 'partial' ? 'info' : 'danger')); 
                                    ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $order['days_open']; ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="../../../modules/purchase/view.php?id=<?php echo $order['purchase_id']; ?>" 
                                           class="btn btn-sm btn-info text-white" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if($order['status'] != 'received' && $order['status'] != 'cancelled'): ?>
                                            <a href="../../../modules/purchase/receive.php?id=<?php echo $order['purchase_id']; ?>" 
                                               class="btn btn-sm btn-success" title="Receive">
                                                <i class="fas fa-truck"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-warning" 
                                                    onclick="sendReminder(<?php echo $order['purchase_id']; ?>)"
                                                    title="Send Reminder">
                                                <i class="fas fa-envelope"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Select all checkboxes
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.order-select').forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Send reminder for single order
function sendReminder(orderId) {
    if(confirm('Send reminder for this purchase order?')) {
        // Add your reminder sending logic here
        alert('Reminder sent successfully');
    }
}

// Send reminders for selected orders
function sendReminders() {
    const selected = Array.from(document.querySelectorAll('.order-select:checked'))
                         .map(checkbox => checkbox.value);
    
    if(selected.length === 0) {
        alert('Please select at least one order');
        return;
    }

    if(confirm('Send reminders for ' + selected.length + ' order(s)?')) {
        // Add your bulk reminder sending logic here
        alert('Reminders sent successfully');
    }
}

// Create status distribution chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'pie',
    data: {
        labels: ['Pending', 'Partially Received', 'Received', 'Cancelled'],
        datasets: [{
            data: [
                <?php echo $po_data['pending_orders']; ?>,
                <?php echo $po_data['partial_orders']; ?>,
                <?php echo $po_data['received_orders']; ?>,
                <?php echo $po_data['cancelled_orders']; ?>
            ],
            backgroundColor: [
                'rgba(255, 193, 7, 0.8)',
                'rgba(23, 162, 184, 0.8)',
                'rgba(40, 167, 69, 0.8)',
                'rgba(220, 53, 69, 0.8)'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': ' + context.raw + ' orders';
                    }
                }
            }
        }
    }
});

// Create monthly trend chart
const trendCtx = document.getElementById('trendChart').getContext('2d');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($po_data['trend_labels']); ?>,
        datasets: [{
            label: 'Number of Orders',
            data: <?php echo json_encode($po_data['trend_values']); ?>,
            borderColor: 'rgb(54, 162, 235)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
