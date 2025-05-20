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
$customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
$group_by = isset($_GET['group_by']) ? $_GET['group_by'] : 'month'; // day, week, month, quarter, year

// Get categories and customers for filters
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT category_id, category_name FROM categories ORDER BY category_name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->query("SELECT customer_id, company_name, first_name, last_name FROM customers ORDER BY company_name, first_name");
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get report data
$revenue_data = $reports->getRevenueAnalysisReport($start_date, $end_date, $category_id, $customer_id, $group_by);

// Include header
require_once '../../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-chart-line"></i> Revenue Analysis</h1>
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
                    <label for="customer_id" class="form-label">Customer</label>
                    <select class="form-select" id="customer_id" name="customer_id">
                        <option value="0">All Customers</option>
                        <?php foreach($customers as $customer): ?>
                            <option value="<?php echo $customer['customer_id']; ?>" 
                                    <?php echo $customer_id == $customer['customer_id'] ? 'selected' : ''; ?>>
                                <?php 
                                echo $customer['company_name'] ? 
                                    htmlspecialchars($customer['company_name']) : 
                                    htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); 
                                ?>
                            </option>
                        <?php endforeach; ?>
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
                <div class="col-md-2">
                    <label class="form-label"> </label>
                    <button type="submit" class="btn btn-primary d-block w-100">
                        <i class="fas fa-filter"></i> Filter
                    </button>
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
                    <h3>$<?php echo number_format($revenue_data['total_revenue'], 2); ?></h3>
                    <small><?php echo $revenue_data['revenue_change']; ?>% vs Previous Period</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Average Revenue</h5>
                    <h3>$<?php echo number_format($revenue_data['average_revenue'], 2); ?></h3>
                    <small>Per <?php echo ucfirst($group_by); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Orders</h5>
                    <h3><?php echo number_format($revenue_data['total_orders']); ?></h3>
                    <small>$<?php echo number_format($revenue_data['average_order'], 2); ?> Avg Order</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Growth Rate</h5>
                    <h3><?php echo number_format($revenue_data['growth_rate'], 1); ?>%</h3>
                    <small>Year over Year</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Trend Chart -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Revenue Trend</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueTrendChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Analysis -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Revenue by Category</h5>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Top 10 Customers</h5>
                </div>
                <div class="card-body">
                    <canvas id="customerChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Revenue Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Revenue Details</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th class="text-end">Revenue</th>
                            <th class="text-end">Orders</th>
                            <th class="text-end">Avg Order</th>
                            <th class="text-end">GST</th>
                            <th class="text-end">Net Revenue</th>
                            <th class="text-end">vs Previous</th>
                            <th>Trend</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($revenue_data['periods'] as $period): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($period['period_name']); ?></td>
                                <td class="text-end">$<?php echo number_format($period['revenue'], 2); ?></td>
                                <td class="text-end"><?php echo number_format($period['orders']); ?></td>
                                <td class="text-end">$<?php echo number_format($period['average_order'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($period['gst'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($period['net_revenue'], 2); ?></td>
                                <td class="text-end">
                                    <span class="text-<?php echo $period['change'] >= 0 ? 'success' : 'danger'; ?>">
                                        <?php echo ($period['change'] >= 0 ? '+' : '') . 
                                              number_format($period['change'], 1); ?>%
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if($period['trend'] > 0): ?>
                                        <i class="fas fa-arrow-up text-success"></i>
                                    <?php elseif($period['trend'] < 0): ?>
                                        <i class="fas fa-arrow-down text-danger"></i>
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
                            <th class="text-end">$<?php echo number_format($revenue_data['total_revenue'], 2); ?></th>
                            <th class="text-end"><?php echo number_format($revenue_data['total_orders']); ?></th>
                            <th class="text-end">$<?php echo number_format($revenue_data['average_order'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($revenue_data['total_gst'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($revenue_data['total_net_revenue'], 2); ?></th>
                            <th class="text-end">
                                <span class="text-<?php echo $revenue_data['revenue_change'] >= 0 ? 'success' : 'danger'; ?>">
                                    <?php echo ($revenue_data['revenue_change'] >= 0 ? '+' : '') . 
                                          number_format($revenue_data['revenue_change'], 1); ?>%
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

// Create revenue trend chart
const trendCtx = document.getElementById('revenueTrendChart').getContext('2d');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($revenue_data['trend_labels']); ?>,
        datasets: [{
            label: 'Revenue',
            data: <?php echo json_encode($revenue_data['trend_values']); ?>,
            borderColor: 'rgb(54, 162, 235)',
            tension: 0.1,
            fill: true,
            backgroundColor: 'rgba(54, 162, 235, 0.1)'
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

// Create category chart
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
new Chart(categoryCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($revenue_data['category_labels']); ?>,
        datasets: [{
            data: <?php echo json_encode($revenue_data['category_values']); ?>,
            backgroundColor: [
                'rgba(54, 162, 235, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(255, 206, 86, 0.8)',
                'rgba(153, 102, 255, 0.8)',
                'rgba(255, 159, 64, 0.8)'
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

// Create customer chart
const customerCtx = document.getElementById('customerChart').getContext('2d');
new Chart(customerCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($revenue_data['customer_labels']); ?>,
        datasets: [{
            label: 'Revenue',
            data: <?php echo json_encode($revenue_data['customer_values']); ?>,
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
</script>

<?php require_once '../../../includes/footer.php'; ?>
