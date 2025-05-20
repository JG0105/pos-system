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
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01', strtotime('-11 months'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;
$group_by = isset($_GET['group_by']) ? $_GET['group_by'] : 'month'; // day, week, month, quarter, year
$expense_type = isset($_GET['expense_type']) ? $_GET['expense_type'] : 'all'; // all, operating, cogs, overhead

// Get categories and suppliers for filters
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT category_id, category_name FROM expense_categories ORDER BY category_name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->query("SELECT supplier_id, company_name FROM suppliers ORDER BY company_name");
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get report data
$expense_data = $reports->getExpenseAnalysisReport($start_date, $end_date, $category_id, $supplier_id, $group_by, $expense_type);

// Include header
require_once '../../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-chart-pie"></i> Expense Analysis</h1>
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
                <div class="col-md-2">
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
                    <label for="expense_type" class="form-label">Expense Type</label>
                    <select class="form-select" id="expense_type" name="expense_type">
                        <option value="all" <?php echo $expense_type == 'all' ? 'selected' : ''; ?>>All Expenses</option>
                        <option value="operating" <?php echo $expense_type == 'operating' ? 'selected' : ''; ?>>Operating Expenses</option>
                        <option value="cogs" <?php echo $expense_type == 'cogs' ? 'selected' : ''; ?>>Cost of Goods Sold</option>
                        <option value="overhead" <?php echo $expense_type == 'overhead' ? 'selected' : ''; ?>>Overhead</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="group_by" class="form-label">Group By</label>
                    <select class="form-select" id="group_by" name="group_by">
                        <option value="day" <?php echo $group_by == 'day' ? 'selected' : ''; ?>>Daily</option>
                        <option value="week" <?php echo $group_by == 'week' ? 'selected' : ''; ?>>Weekly</option>
                        <option value="month" <?php echo $group_by == 'month' ? 'selected' : ''; ?>>Monthly</option>
                        <option value="quarter" <?php echo $group_by == 'quarter' ? 'selected' : ''; ?>>Quarterly</option>
                        <option value="year" <?php echo $group_by == 'year' ? 'selected' : ''; ?>>Yearly</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="expense_analysis.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Expenses</h5>
                    <h3>$<?php echo number_format($expense_data['total_expenses'], 2); ?></h3>
                    <small><?php echo $expense_data['expense_change']; ?>% vs Previous Period</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Average Monthly</h5>
                    <h3>$<?php echo number_format($expense_data['average_monthly'], 2); ?></h3>
                    <small>Per Month</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">GST Paid</h5>
                    <h3>$<?php echo number_format($expense_data['total_gst'], 2); ?></h3>
                    <small>Total GST</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Largest Category</h5>
                    <h3>$<?php echo number_format($expense_data['largest_category']['amount'], 2); ?></h3>
                    <small><?php echo htmlspecialchars($expense_data['largest_category']['name']); ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Expense Charts -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Expense Trend</h5>
                </div>
                <div class="card-body">
                    <canvas id="trendChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Expense Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="distributionChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Expenses -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Top Categories</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">% of Total</th>
                                    <th class="text-end">vs Previous</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($expense_data['top_categories'] as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td class="text-end">$<?php echo number_format($category['amount'], 2); ?></td>
                                        <td class="text-end"><?php echo number_format($category['percentage'], 1); ?>%</td>
                                        <td class="text-end">
                                            <span class="text-<?php echo $category['change'] <= 0 ? 'success' : 'danger'; ?>">
                                                <?php echo ($category['change'] >= 0 ? '+' : '') . 
                                                      number_format($category['change'], 1); ?>%
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Top Suppliers</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Supplier</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">% of Total</th>
                                    <th class="text-end">vs Previous</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($expense_data['top_suppliers'] as $supplier): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($supplier['name']); ?></td>
                                        <td class="text-end">$<?php echo number_format($supplier['amount'], 2); ?></td>
                                        <td class="text-end"><?php echo number_format($supplier['percentage'], 1); ?>%</td>
                                        <td class="text-end">
                                            <span class="text-<?php echo $supplier['change'] <= 0 ? 'success' : 'danger'; ?>">
                                                <?php echo ($supplier['change'] >= 0 ? '+' : '') . 
                                                      number_format($supplier['change'], 1); ?>%
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Expense Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Expense Details</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th class="text-end">Operating</th>
                            <th class="text-end">COGS</th>
                            <th class="text-end">Overhead</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">GST</th>
                            <th class="text-end">vs Previous</th>
                            <th>Trend</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($expense_data['periods'] as $period): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($period['period_name']); ?></td>
                                <td class="text-end">$<?php echo number_format($period['operating'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($period['cogs'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($period['overhead'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($period['total'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($period['gst'], 2); ?></td>
                                <td class="text-end">
                                    <span class="text-<?php echo $period['change'] <= 0 ? 'success' : 'danger'; ?>">
                                        <?php echo ($period['change'] >= 0 ? '+' : '') . 
                                              number_format($period['change'], 1); ?>%
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if($period['trend'] > 0): ?>
                                        <i class="fas fa-arrow-up text-danger"></i>
                                    <?php elseif($period['trend'] < 0): ?>
                                        <i class="fas fa-arrow-down text-success"></i>
                                    <?php else: ?>
                                        <i class="fas fa-minus text-muted"></i>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th>Total</th>
                            <th class="text-end">$<?php echo number_format($expense_data['total_operating'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($expense_data['total_cogs'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($expense_data['total_overhead'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($expense_data['total_expenses'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($expense_data['total_gst'], 2); ?></th>
                            <th class="text-end">
                                <span class="text-<?php echo $expense_data['expense_change'] <= 0 ? 'success' : 'danger'; ?>">
                                    <?php echo ($expense_data['expense_change'] >= 0 ? '+' : '') . 
                                          number_format($expense_data['expense_change'], 1); ?>%
                                </span>
                            </th>
                            <th></th>
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
        labels: <?php echo json_encode($expense_data['trend_labels']); ?>,
        datasets: [{
            label: 'Total Expenses',
            data: <?php echo json_encode($expense_data['trend_values']); ?>,
            borderColor: 'rgb(255, 99, 132)',
            tension: 0.1,
            fill: true,
            backgroundColor: 'rgba(255, 99, 132, 0.1)'
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
        labels: ['Operating', 'COGS', 'Overhead'],
        datasets: [{
            data: [
                <?php echo $expense_data['total_operating']; ?>,
                <?php echo $expense_data['total_cogs']; ?>,
                <?php echo $expense_data['total_overhead']; ?>
            ],
            backgroundColor: [
                'rgba(255, 99, 132, 0.8)',
                'rgba(54, 162, 235, 0.8)',
                'rgba(255, 206, 86, 0.8)'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': $' + context.raw.toLocaleString();
                    }
                }
            }
        }
    }
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
