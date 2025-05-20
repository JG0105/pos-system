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
$min_amount = isset($_GET['min_amount']) ? (float)$_GET['min_amount'] : 0;
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'amount'; // amount, orders, latest

// Get suppliers for filter
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT supplier_id, company_name FROM suppliers ORDER BY company_name");
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get report data
$supplier_data = $reports->getPurchasesBySupplierReport($start_date, $end_date, $supplier_id, $min_amount, $sort_by);

// Include header
require_once '../../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-truck"></i> Purchases by Supplier</h1>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-primary" onclick="window.print();">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="purchases_by_supplier_pdf.php?<?php echo http_build_query($_GET); ?>" 
                   class="btn btn-danger" target="_blank">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
                <a href="purchases_by_supplier_excel.php?<?php echo http_build_query($_GET); ?>" 
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
                    <label for="min_amount" class="form-label">Minimum Amount</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="min_amount" name="min_amount" 
                               value="<?php echo $min_amount; ?>" min="0" step="0.01">
                    </div>
                </div>
                <div class="col-md-2">
                    <label for="sort_by" class="form-label">Sort By</label>
                    <select class="form-select" id="sort_by" name="sort_by">
                        <option value="amount" <?php echo $sort_by == 'amount' ? 'selected' : ''; ?>>Total Amount</option>
                        <option value="orders" <?php echo $sort_by == 'orders' ? 'selected' : ''; ?>>Number of Orders</option>
                        <option value="latest" <?php echo $sort_by == 'latest' ? 'selected' : ''; ?>>Latest Purchase</option>
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
                    <h5 class="card-title">Total Purchases</h5>
                    <h3>$<?php echo number_format($supplier_data['total_amount'], 2); ?></h3>
                    <small><?php echo $supplier_data['total_orders']; ?> Orders</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Active Suppliers</h5>
                    <h3><?php echo number_format($supplier_data['active_suppliers']); ?></h3>
                    <small>With Purchases</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Average Order</h5>
                    <h3>$<?php echo number_format($supplier_data['average_order'], 2); ?></h3>
                    <small>Per Purchase</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Top Supplier</h5>
                    <h3>$<?php echo number_format($supplier_data['top_supplier']['amount'], 2); ?></h3>
                    <small><?php echo htmlspecialchars($supplier_data['top_supplier']['name']); ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Supplier Distribution Chart -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Top 10 Suppliers by Purchase Amount</h5>
                </div>
                <div class="card-body">
                    <canvas id="suppliersChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Purchase Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="distributionChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Suppliers Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Supplier Purchase Details</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Supplier</th>
                            <th class="text-end">Orders</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">GST</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Avg Order</th>
                            <th class="text-end">Last Purchase</th>
                            <th class="text-end">Outstanding</th>
                            <th>Trend</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($supplier_data['suppliers'] as $supplier): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($supplier['company_name']); ?></td>
                                <td class="text-end"><?php echo number_format($supplier['order_count']); ?></td>
                                <td class="text-end">$<?php echo number_format($supplier['total_amount'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($supplier['total_gst'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($supplier['total_with_gst'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($supplier['average_order'], 2); ?></td>
                                <td class="text-end"><?php echo date('d/m/Y', strtotime($supplier['last_purchase'])); ?></td>
                                <td class="text-end">$<?php echo number_format($supplier['outstanding'], 2); ?></td>
                                <td class="text-center">
                                    <?php if($supplier['trend'] > 0): ?>
                                        <i class="fas fa-arrow-up text-success"></i>
                                    <?php elseif($supplier['trend'] < 0): ?>
                                        <i class="fas fa-arrow-down text-danger"></i>
                                    <?php else: ?>
                                        <i class="fas fa-minus text-muted"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="supplier_detail.php?id=<?php echo $supplier['supplier_id']; ?>" 
                                       class="btn btn-sm btn-info text-white" title="View Details">
                                        <i class="fas fa-search"></i>
                                    </a>
                                    <a href="../../../modules/suppliers/view.php?id=<?php echo $supplier['supplier_id']; ?>" 
                                       class="btn btn-sm btn-primary" title="View Supplier">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th>Total</th>
                            <th class="text-end"><?php echo number_format($supplier_data['total_orders']); ?></th>
                            <th class="text-end">$<?php echo number_format($supplier_data['total_amount'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($supplier_data['total_gst'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($supplier_data['total_with_gst'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($supplier_data['average_order'], 2); ?></th>
                            <th colspan="4"></th>
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

// Create suppliers chart
const suppliersCtx = document.getElementById('suppliersChart').getContext('2d');
new Chart(suppliersCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_slice($supplier_data['chart_labels'], 0, 10)); ?>,
        datasets: [{
            label: 'Purchase Amount',
            data: <?php echo json_encode(array_slice($supplier_data['chart_values'], 0, 10)); ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.5)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
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

// Create distribution chart
const distributionCtx = document.getElementById('distributionChart').getContext('2d');
new Chart(distributionCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($supplier_data['distribution_labels']); ?>,
        datasets: [{
            data: <?php echo json_encode($supplier_data['distribution_values']); ?>,
            backgroundColor: [
                'rgba(54, 162, 235, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(255, 206, 86, 0.8)',
                'rgba(153, 102, 255, 0.8)'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': ' + context.raw.toFixed(1) + '%';
                    }
                }
            }
        }
    }
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
