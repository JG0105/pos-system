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
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01', strtotime('-6 months'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'revenue'; // revenue, profit, turnover, margin
$performance = isset($_GET['performance']) ? $_GET['performance'] : 'all'; // all, top, bottom

// Get categories for filter
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT category_id, category_name FROM categories ORDER BY category_name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get report data
$performance_data = $reports->getProductPerformanceReport($start_date, $end_date, $category_id, $sort_by, $performance);

// Include header
require_once '../../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-chart-line"></i> Product Performance Analysis</h1>
        </div>
        <div class="col-md-6 text-end">
            <button type="button" class="btn btn-primary" onclick="window.print();">
                <i class="fas fa-print"></i> Print Report
            </button>
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
                    <label for="category_id" class="form-label">Category</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="0">All Categories</option>
                        <?php foreach($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>" 
                                    <?php echo $category_id == $category['category_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="sort_by" class="form-label">Sort By</label>
                    <select class="form-select" id="sort_by" name="sort_by">
                        <option value="revenue" <?php echo $sort_by == 'revenue' ? 'selected' : ''; ?>>Revenue</option>
                        <option value="profit" <?php echo $sort_by == 'profit' ? 'selected' : ''; ?>>Profit</option>
                        <option value="turnover" <?php echo $sort_by == 'turnover' ? 'selected' : ''; ?>>Turnover Rate</option>
                        <option value="margin" <?php echo $sort_by == 'margin' ? 'selected' : ''; ?>>Profit Margin</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="performance" class="form-label">Performance</label>
                    <select class="form-select" id="performance" name="performance">
                        <option value="all" <?php echo $performance == 'all' ? 'selected' : ''; ?>>All Products</option>
                        <option value="top" <?php echo $performance == 'top' ? 'selected' : ''; ?>>Top Performers</option>
                        <option value="bottom" <?php echo $performance == 'bottom' ? 'selected' : ''; ?>>Under Performers</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label"> </label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i>
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
                    <h5 class="card-title">Total Revenue</h5>
                    <h3>$<?php echo number_format($performance_data['total_revenue'], 2); ?></h3>
                    <small><?php echo number_format($performance_data['total_units']); ?> Units Sold</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Profit</h5>
                    <h3>$<?php echo number_format($performance_data['total_profit'], 2); ?></h3>
                    <small><?php echo number_format($performance_data['average_margin'], 1); ?>% Margin</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Best Seller</h5>
                    <h3><?php echo number_format($performance_data['best_seller']['units']); ?></h3>
                    <small><?php echo htmlspecialchars($performance_data['best_seller']['name']); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Most Profitable</h5>
                    <h3>$<?php echo number_format($performance_data['most_profitable']['profit'], 2); ?></h3>
                    <small><?php echo htmlspecialchars($performance_data['most_profitable']['name']); ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Charts -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Top 10 Products by Revenue</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Profit Margin Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="marginChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Product Performance Details</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th class="text-end">Units Sold</th>
                            <th class="text-end">Revenue</th>
                            <th class="text-end">Cost</th>
                            <th class="text-end">Profit</th>
                            <th class="text-end">Margin</th>
                            <th class="text-end">Turnover Rate</th>
                            <th>Trend</th>
                            <th>Performance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($performance_data['products'] as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td class="text-end"><?php echo number_format($product['units_sold']); ?></td>
                                <td class="text-end">$<?php echo number_format($product['revenue'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($product['cost'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($product['profit'], 2); ?></td>
                                <td class="text-end"><?php echo number_format($product['margin'], 1); ?>%</td>
                                <td class="text-end"><?php echo number_format($product['turnover'], 1); ?></td>
                                <td class="text-center">
                                    <?php if($product['trend'] > 0): ?>
                                        <i class="fas fa-arrow-up text-success" title="Increasing"></i>
                                    <?php elseif($product['trend'] < 0): ?>
                                        <i class="fas fa-arrow-down text-danger" title="Decreasing"></i>
                                    <?php else: ?>
                                        <i class="fas fa-minus text-muted" title="Stable"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $product['performance'] == 'high' ? 'success' : 
                                            ($product['performance'] == 'medium' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($product['performance']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="../../../modules/products/view.php?id=<?php echo $product['product_id']; ?>" 
                                           class="btn btn-sm btn-info text-white" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="product_detail.php?id=<?php echo $product['product_id']; ?>" 
                                           class="btn btn-sm btn-primary" title="Performance Details">
                                            <i class="fas fa-chart-bar"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th colspan="2">Total</th>
                            <th class="text-end"><?php echo number_format($performance_data['total_units']); ?></th>
                            <th class="text-end">$<?php echo number_format($performance_data['total_revenue'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($performance_data['total_cost'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($performance_data['total_profit'], 2); ?></th>
                            <th class="text-end"><?php echo number_format($performance_data['average_margin'], 1); ?>%</th>
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

// Create revenue chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_slice($performance_data['top_products']['names'], 0, 10)); ?>,
        datasets: [{
            label: 'Revenue',
            data: <?php echo json_encode(array_slice($performance_data['top_products']['revenue'], 0, 10)); ?>,
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

// Create margin distribution chart
const marginCtx = document.getElementById('marginChart').getContext('2d');
new Chart(marginCtx, {
    type: 'pie',
    data: {
        labels: ['High Margin (>30%)', 'Medium Margin (15-30%)', 'Low Margin (<15%)'],
        datasets: [{
            data: [
                <?php echo $performance_data['margin_distribution']['high']; ?>,
                <?php echo $performance_data['margin_distribution']['medium']; ?>,
                <?php echo $performance_data['margin_distribution']['low']; ?>
            ],
            backgroundColor: [
                'rgba(40, 167, 69, 0.8)',
                'rgba(255, 193, 7, 0.8)',
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
                        return context.label + ': ' + context.raw + ' products';
                    }
                }
            }
        }
    }
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
