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
$payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';

// Get report data
$payment_data = $reports->getPaymentMethodAnalysis($start_date, $end_date, $payment_method);

// Include header
require_once '../../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-money-bill-wave"></i> Payment Method Analysis</h1>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-primary" onclick="window.print();">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="payment_analysis_pdf.php?<?php echo http_build_query($_GET); ?>" 
                   class="btn btn-danger" target="_blank">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
                <a href="payment_analysis_excel.php?<?php echo http_build_query($_GET); ?>" 
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
                    <label for="payment_method" class="form-label">Payment Method</label>
                    <select class="form-select" id="payment_method" name="payment_method">
                        <option value="">All Methods</option>
                        <option value="cash" <?php echo $payment_method == 'cash' ? 'selected' : ''; ?>>Cash</option>
                        <option value="card" <?php echo $payment_method == 'card' ? 'selected' : ''; ?>>Card</option>
                        <option value="direct_deposit" <?php echo $payment_method == 'direct_deposit' ? 'selected' : ''; ?>>Direct Deposit</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"> </label>
                    <div class="d-grid gap-2">
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
                    <h5 class="card-title">Total Sales</h5>
                    <h3>$<?php echo number_format($payment_data['total_sales'], 2); ?></h3>
                    <small><?php echo number_format($payment_data['total_transactions']); ?> Transactions</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Average Transaction</h5>
                    <h3>$<?php echo number_format($payment_data['average_transaction'], 2); ?></h3>
                    <small>Per Sale</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Most Used Method</h5>
                    <h3><?php echo ucfirst(str_replace('_', ' ', $payment_data['most_used_method']['method'])); ?></h3>
                    <small><?php echo number_format($payment_data['most_used_method']['count']); ?> Transactions</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Highest Value Method</h5>
                    <h3><?php echo ucfirst(str_replace('_', ' ', $payment_data['highest_value_method']['method'])); ?></h3>
                    <small>$<?php echo number_format($payment_data['highest_value_method']['amount'], 2); ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Methods Breakdown -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payment Methods Summary</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Payment Method</th>
                                    <th class="text-end">Transactions</th>
                                    <th class="text-end">Total Amount</th>
                                    <th class="text-end">Average Amount</th>
                                    <th class="text-end">% of Sales</th>
                                    <th>Trend</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($payment_data['methods'] as $method): ?>
                                    <tr>
                                        <td><?php echo ucfirst(str_replace('_', ' ', $method['method'])); ?></td>
                                        <td class="text-end"><?php echo number_format($method['count']); ?></td>
                                        <td class="text-end">$<?php echo number_format($method['amount'], 2); ?></td>
                                        <td class="text-end">$<?php echo number_format($method['average'], 2); ?></td>
                                        <td class="text-end"><?php echo number_format($method['percentage'], 1); ?>%</td>
                                        <td class="text-center">
                                            <?php if($method['trend'] > 0): ?>
                                                <i class="fas fa-arrow-up text-success"></i>
                                            <?php elseif($method['trend'] < 0): ?>
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
                                    <th class="text-end"><?php echo number_format($payment_data['total_transactions']); ?></th>
                                    <th class="text-end">$<?php echo number_format($payment_data['total_sales'], 2); ?></th>
                                    <th class="text-end">$<?php echo number_format($payment_data['average_transaction'], 2); ?></th>
                                    <th class="text-end">100%</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payment Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="paymentChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Breakdown -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Daily Payment Breakdown</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th class="text-end">Cash</th>
                            <th class="text-end">Card</th>
                            <th class="text-end">Direct Deposit</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Transactions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($payment_data['daily_breakdown'] as $day): ?>
                            <tr>
                                <td><?php echo date('D, j M', strtotime($day['date'])); ?></td>
                                <td class="text-end">$<?php echo number_format($day['cash'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($day['card'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($day['direct_deposit'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($day['total'], 2); ?></td>
                                <td class="text-end"><?php echo number_format($day['transactions']); ?></td>
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
// Add date validation
document.getElementById('start_date').addEventListener('change', function() {
    document.getElementById('end_date').min = this.value;
});

document.getElementById('end_date').addEventListener('change', function() {
    document.getElementById('start_date').max = this.value;
});

// Create payment distribution chart
const ctx = document.getElementById('paymentChart').getContext('2d');
new Chart(ctx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode(array_map(function($method) {
            return ucfirst(str_replace('_', ' ', $method['method']));
        }, $payment_data['methods'])); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($payment_data['methods'], 'percentage')); ?>,
            backgroundColor: [
                'rgba(54, 162, 235, 0.8)',
                'rgba(75, 192, 192, 0.8)',
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
                        return context.label + ': ' + context.raw.toFixed(1) + '%';
                    }
                }
            }
        }
    }
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
