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
$comparison = isset($_GET['comparison']) ? $_GET['comparison'] : 'previous'; // previous, year
$format = isset($_GET['format']) ? $_GET['format'] : 'monthly'; // monthly, quarterly, yearly

// Get report data
$pl_data = $reports->getProfitLossReport($start_date, $end_date, $comparison, $format);

// Include header
require_once '../../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-chart-pie"></i> Profit & Loss Statement</h1>
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
                <div class="col-md-2">
                    <label for="comparison" class="form-label">Comparison</label>
                    <select class="form-select" id="comparison" name="comparison">
                        <option value="previous" <?php echo $comparison == 'previous' ? 'selected' : ''; ?>>Previous Period</option>
                        <option value="year" <?php echo $comparison == 'year' ? 'selected' : ''; ?>>Previous Year</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="format" class="form-label">Format</label>
                    <select class="form-select" id="format" name="format">
                        <option value="monthly" <?php echo $format == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                        <option value="quarterly" <?php echo $format == 'quarterly' ? 'selected' : ''; ?>>Quarterly</option>
                        <option value="yearly" <?php echo $format == 'yearly' ? 'selected' : ''; ?>>Yearly</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"> </label>
                    <button type="submit" class="btn btn-primary d-block w-100">
                        <i class="fas fa-sync"></i> Update
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
                    <h5 class="card-title">Net Profit/Loss</h5>
                    <h3>$<?php echo number_format($pl_data['net_profit'], 2); ?></h3>
                    <small><?php echo $pl_data['profit_margin']; ?>% Margin</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Revenue</h5>
                    <h3>$<?php echo number_format($pl_data['total_revenue'], 2); ?></h3>
                    <small><?php echo $pl_data['revenue_change']; ?>% vs Previous</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Expenses</h5>
                    <h3>$<?php echo number_format($pl_data['total_expenses'], 2); ?></h3>
                    <small><?php echo $pl_data['expense_change']; ?>% vs Previous</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Gross Profit</h5>
                    <h3>$<?php echo number_format($pl_data['gross_profit'], 2); ?></h3>
                    <small><?php echo $pl_data['gross_margin']; ?>% Margin</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Trend Charts -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Profit & Loss Trend</h5>
                </div>
                <div class="card-body">
                    <canvas id="trendChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Revenue Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="distributionChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- P&L Statement -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Profit & Loss Statement</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th class="text-end">Current Period</th>
                            <th class="text-end">Previous Period</th>
                            <th class="text-end">Variance</th>
                            <th class="text-end">Variance %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Revenue Section -->
                        <tr class="table-primary">
                            <th colspan="5">Revenue</th>
                        </tr>
                        <?php foreach($pl_data['revenue_items'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td class="text-end">$<?php echo number_format($item['current'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($item['previous'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($item['variance'], 2); ?></td>
                                <td class="text-end">
                                    <span class="text-<?php echo $item['variance_percent'] >= 0 ? 'success' : 'danger'; ?>">
                                        <?php echo ($item['variance_percent'] >= 0 ? '+' : '') . 
                                              number_format($item['variance_percent'], 1); ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="table-success">
                            <th>Total Revenue</th>
                            <th class="text-end">$<?php echo number_format($pl_data['total_revenue'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($pl_data['previous_revenue'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($pl_data['revenue_variance'], 2); ?></th>
                            <th class="text-end">
                                <span class="text-<?php echo $pl_data['revenue_change'] >= 0 ? 'success' : 'danger'; ?>">
                                    <?php echo ($pl_data['revenue_change'] >= 0 ? '+' : '') . 
                                          number_format($pl_data['revenue_change'], 1); ?>%
                                </span>
                            </th>
                        </tr>

                        <!-- Cost of Sales Section -->
                        <tr class="table-primary">
                            <th colspan="5">Cost of Sales</th>
                        </tr>
                        <?php foreach($pl_data['cos_items'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td class="text-end">$<?php echo number_format($item['current'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($item['previous'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($item['variance'], 2); ?></td>
                                <td class="text-end">
                                    <span class="text-<?php echo $item['variance_percent'] <= 0 ? 'success' : 'danger'; ?>">
                                        <?php echo ($item['variance_percent'] >= 0 ? '+' : '') . 
                                              number_format($item['variance_percent'], 1); ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="table-warning">
                            <th>Gross Profit</th>
                            <th class="text-end">$<?php echo number_format($pl_data['gross_profit'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($pl_data['previous_gross_profit'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($pl_data['gross_profit_variance'], 2); ?></th>
                            <th class="text-end">
                                <span class="text-<?php echo $pl_data['gross_profit_change'] >= 0 ? 'success' : 'danger'; ?>">
                                    <?php echo ($pl_data['gross_profit_change'] >= 0 ? '+' : '') . 
                                          number_format($pl_data['gross_profit_change'], 1); ?>%
                                </span>
                            </th>
                        </tr>

                        <!-- Operating Expenses Section -->
                        <tr class="table-primary">
                            <th colspan="5">Operating Expenses</th>
                        </tr>
                        <?php foreach($pl_data['expense_items'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td class="text-end">$<?php echo number_format($item['current'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($item['previous'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($item['variance'], 2); ?></td>
                                <td class="text-end">
                                    <span class="text-<?php echo $item['variance_percent'] <= 0 ? 'success' : 'danger'; ?>">
                                        <?php echo ($item['variance_percent'] >= 0 ? '+' : '') . 
                                              number_format($item['variance_percent'], 1); ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="table-info">
                            <th>Total Expenses</th>
                            <th class="text-end">$<?php echo number_format($pl_data['total_expenses'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($pl_data['previous_expenses'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($pl_data['expense_variance'], 2); ?></th>
                            <th class="text-end">
                                <span class="text-<?php echo $pl_data['expense_change'] <= 0 ? 'success' : 'danger'; ?>">
                                    <?php echo ($pl_data['expense_change'] >= 0 ? '+' : '') . 
                                          number_format($pl_data['expense_change'], 1); ?>%
                                </span>
                            </th>
                        </tr>

                        <!-- Net Profit Section -->
                        <tr class="table-primary">
                            <th>Net Profit/Loss</th>
                            <th class="text-end">$<?php echo number_format($pl_data['net_profit'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($pl_data['previous_net_profit'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($pl_data['net_profit_variance'], 2); ?></th>
                            <th class="text-end">
                                <span class="text-<?php echo $pl_data['net_profit_change'] >= 0 ? 'success' : 'danger'; ?>">
                                    <?php echo ($pl_data['net_profit_change'] >= 0 ? '+' : '') . 
                                          number_format($pl_data['net_profit_change'], 1); ?>%
                                </span>
                            </th>
                        </tr>
                    </tbody>
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
        labels: <?php echo json_encode($pl_data['trend_labels']); ?>,
        datasets: [{
            label: 'Revenue',
            data: <?php echo json_encode($pl_data['revenue_trend']); ?>,
            borderColor: 'rgb(40, 167, 69)',
            tension: 0.1
        },
        {
            label: 'Expenses',
            data: <?php echo json_encode($pl_data['expense_trend']); ?>,
            borderColor: 'rgb(220, 53, 69)',
            tension: 0.1
        },
        {
            label: 'Net Profit',
            data: <?php echo json_encode($pl_data['profit_trend']); ?>,
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

// Create distribution chart
const distributionCtx = document.getElementById('distributionChart').getContext('2d');
new Chart(distributionCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($pl_data['revenue_categories']); ?>,
        datasets: [{
            data: <?php echo json_encode($pl_data['revenue_distribution']); ?>,
            backgroundColor: [
                'rgba(40, 167, 69, 0.8)',
                'rgba(54, 162, 235, 0.8)',
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
                        return context.label + ': ' + context.raw.toFixed(1) + '%';
                    }
                }
            }
        }
    }
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
