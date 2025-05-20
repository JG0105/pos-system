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
$status = isset($_GET['status']) ? $_GET['status'] : 'all'; // all, pending, received, cancelled

// Get report data
$purchase_data = $reports->getPurchaseSummaryReport($start_date, $end_date, $status);

// Include header
require_once '../../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-shopping-basket"></i> Purchase Summary Report</h1>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-primary" onclick="window.print();">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="purchase_summary_pdf.php?<?php echo http_build_query($_GET); ?>" 
                   class="btn btn-danger" target="_blank">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
                <a href="purchase_summary_excel.php?<?php echo http_build_query($_GET); ?>" 
                   class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="received" <?php echo $status == 'received' ? 'selected' : ''; ?>>Received</option>
                        <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"> </label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Update Report
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
                    <h5 class="card-title">Total Purchases</h5>
                    <h3>$<?php echo number_format($purchase_data['total_amount'], 2); ?></h3>
                    <small><?php echo $purchase_data['total_orders']; ?> Orders</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">GST Paid</h5>
                    <h3>$<?php echo number_format($purchase_data['total_gst'], 2); ?></h3>
                    <small>Total GST</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Average Order</h5>
                    <h3>$<?php echo number_format($purchase_data['average_order'], 2); ?></h3>
                    <small>Per Order</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Outstanding</h5>
                    <h3>$<?php echo number_format($purchase_data['total_outstanding'], 2); ?></h3>
                    <small>Unpaid Amount</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Trend Chart -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Monthly Purchase Trend</h5>
                </div>
                <div class="card-body">
                    <canvas id="trendChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Status Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="300"></canvas>
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
                            <th>Date</th>
                            <th>PO #</th>
                            <th>Supplier</th>
                            <th>Reference</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">GST</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Paid</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($purchase_data['orders'] as $order): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($order['purchase_date'])); ?></td>
                                <td><?php echo 'PO-' . str_pad($order['purchase_id'], 6, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($order['supplier_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['reference_no']); ?></td>
                                <td class="text-end">$<?php echo number_format($order['subtotal'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($order['tax_amount'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($order['amount_paid'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $order['status'] == 'received' ? 'success' : 
                                            ($order['status'] == 'pending' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="../../../modules/purchase/view.php?id=<?php echo $order['purchase_id']; ?>" 
                                       class="btn btn-sm btn-info text-white" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="../../../modules/purchase/print.php?id=<?php echo $order['purchase_id']; ?>" 
                                       class="btn btn-sm btn-secondary" title="Print" target="_blank">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th colspan="4">Total</th>
                            <th class="text-end">$<?php echo number_format($purchase_data['total_subtotal'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($purchase_data['total_gst'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($purchase_data['total_amount'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($purchase_data['total_paid'], 2); ?></th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Add date validation
document.getElementById('start_date').addEventListener('change', function() {
    document.getElementById('end_date').min = this.value;
});

document.getElementById('end_date').addEventListener('change', function() {
    document.getElementById('start_date').max = this.value;
});

// Create trend chart
const trendCtx = document.getElementById('trendChart').getContext('2d');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($purchase_data['trend_labels']); ?>,
        datasets: [{
            label: 'Purchase Amount',
            data: <?php echo json_encode($purchase_data['trend_values']); ?>,
            borderColor: 'rgb(54, 162, 235)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return '$' + context.raw.toLocaleString();
                    }
                }
            }
        }
    }
});

// Create status distribution chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'pie',
    data: {
        labels: ['Received', 'Pending', 'Cancelled'],
        datasets: [{
            data: [
                <?php echo $purchase_data['status_received']; ?>,
                <?php echo $purchase_data['status_pending']; ?>,
                <?php echo $purchase_data['status_cancelled']; ?>
            ],
            backgroundColor: [
                'rgb(40, 167, 69)',
                'rgb(255, 193, 7)',
                'rgb(220, 53, 69)'
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
</script>

<?php require_once '../../../includes/footer.php'; ?>
