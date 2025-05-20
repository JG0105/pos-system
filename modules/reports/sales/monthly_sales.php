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

// Get month and year parameters with defaults
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Get report data
$monthly_sales = $reports->getMonthlySalesReport($month, $year);

// Calculate month start and end dates
$start_date = date('Y-m-01', strtotime("$year-$month-01"));
$end_date = date('Y-m-t', strtotime("$year-$month-01"));

// Include header
require_once '../../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-chart-bar"></i> Monthly Sales Report</h1>
            <h5 class="text-muted"><?php echo date('F Y', strtotime("$year-$month-01")); ?></h5>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-primary" onclick="window.print();">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="monthly_sales_pdf.php?month=<?php echo $month; ?>&year=<?php echo $year; ?>" 
                   class="btn btn-danger" target="_blank">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
                <a href="monthly_sales_excel.php?month=<?php echo $month; ?>&year=<?php echo $year; ?>" 
                   class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
            </div>
        </div>
    </div>

    <!-- Month Selection -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="month" class="form-label">Month</label>
                    <select class="form-select" id="month" name="month">
                        <?php for($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php echo $m == $month ? 'selected' : ''; ?>>
                                <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="year" class="form-label">Year</label>
                    <select class="form-select" id="year" name="year">
                        <?php for($y = date('Y'); $y >= date('Y')-5; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>>
                                <?php echo $y; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label"> </label>
                    <button type="submit" class="btn btn-primary d-block w-100">
                        <i class="fas fa-sync"></i> Update Report
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
                    <h5 class="card-title">Total Sales</h5>
                    <h3>$<?php echo number_format($monthly_sales['total_sales'], 2); ?></h3>
                    <small><?php echo $monthly_sales['total_transactions']; ?> Transactions</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Average Daily Sales</h5>
                    <h3>$<?php echo number_format($monthly_sales['average_daily_sales'], 2); ?></h3>
                    <small>Per Business Day</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">GST Collected</h5>
                    <h3>$<?php echo number_format($monthly_sales['total_gst'], 2); ?></h3>
                    <small>Total GST</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Average Transaction</h5>
                    <h3>$<?php echo number_format($monthly_sales['average_transaction'], 2); ?></h3>
                    <small>Per Sale</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Breakdown -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Daily Sales Breakdown</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th class="text-end">Sales</th>
                                    <th class="text-end">GST</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Transactions</th>
                                    <th class="text-end">Average Sale</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($monthly_sales['daily_sales'] as $day): ?>
                                    <tr>
                                        <td><?php echo date('D, j M', strtotime($day['date'])); ?></td>
                                        <td class="text-end">$<?php echo number_format($day['sales'], 2); ?></td>
                                        <td class="text-end">$<?php echo number_format($day['gst'], 2); ?></td>
                                        <td class="text-end">$<?php echo number_format($day['total'], 2); ?></td>
                                        <td class="text-end"><?php echo $day['transactions']; ?></td>
                                        <td class="text-end">$<?php echo number_format($day['average'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-primary">
                                    <th>Total</th>
                                    <th class="text-end">$<?php echo number_format($monthly_sales['total_sales'], 2); ?></th>
                                    <th class="text-end">$<?php echo number_format($monthly_sales['total_gst'], 2); ?></th>
                                    <th class="text-end">$<?php echo number_format($monthly_sales['total_with_gst'], 2); ?></th>
                                    <th class="text-end"><?php echo $monthly_sales['total_transactions']; ?></th>
                                    <th class="text-end">$<?php echo number_format($monthly_sales['average_transaction'], 2); ?></th>
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
                    <h5 class="card-title mb-0">Payment Methods</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Method</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($monthly_sales['payment_methods'] as $method): ?>
                                    <tr>
                                        <td><?php echo ucfirst(str_replace('_', ' ', $method['method'])); ?></td>
                                        <td class="text-end">$<?php echo number_format($method['amount'], 2); ?></td>
                                        <td class="text-end"><?php echo $method['count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-primary">
                                    <th>Total</th>
                                    <th class="text-end">$<?php echo number_format($monthly_sales['total_with_gst'], 2); ?></th>
                                    <th class="text-end"><?php echo $monthly_sales['total_transactions']; ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Products -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Top Selling Products</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-end">Quantity Sold</th>
                            <th class="text-end">Total Sales</th>
                            <th class="text-end">Average Price</th>
                            <th class="text-end">GST</th>
                            <th class="text-end">Total with GST</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($monthly_sales['top_products'] as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td class="text-end"><?php echo $product['quantity']; ?></td>
                                <td class="text-end">$<?php echo number_format($product['sales'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($product['average_price'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($product['gst'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($product['total'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>
