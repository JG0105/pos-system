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

// Get date parameter with default to today
$report_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get report data
$daily_sales = $reports->getDailySalesReport($report_date);

// Include header
require_once '../../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-chart-line"></i> Daily Sales Report</h1>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-primary" onclick="window.print();">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="daily_sales_pdf.php?date=<?php echo $report_date; ?>" 
                   class="btn btn-danger" target="_blank">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
                <a href="daily_sales_excel.php?date=<?php echo $report_date; ?>" 
                   class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
            </div>
        </div>
    </div>

    <!-- Date Selection -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="date" class="form-label">Select Date</label>
                    <input type="date" class="form-control" id="date" name="date" 
                           value="<?php echo $report_date; ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label"> </label>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sync"></i> Update Report
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label"> </label>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-secondary" 
                                onclick="location.href='daily_sales.php'">
                            <i class="fas fa-calendar-day"></i> Today's Report
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
                    <h3>$<?php echo number_format($daily_sales['total_sales'], 2); ?></h3>
                    <small><?php echo $daily_sales['total_transactions']; ?> Transactions</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Net Sales</h5>
                    <h3>$<?php echo number_format($daily_sales['net_sales'], 2); ?></h3>
                    <small>Excluding GST</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">GST Collected</h5>
                    <h3>$<?php echo number_format($daily_sales['gst_amount'], 2); ?></h3>
                    <small>Total GST</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Average Sale</h5>
                    <h3>$<?php echo number_format($daily_sales['average_sale'], 2); ?></h3>
                    <small>Per Transaction</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Method Breakdown -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payment Methods</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Payment Method</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">Transactions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($daily_sales['payment_methods'] as $method): ?>
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
                                    <th class="text-end">$<?php echo number_format($daily_sales['total_sales'], 2); ?></th>
                                    <th class="text-end"><?php echo $daily_sales['total_transactions']; ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Hourly Sales</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Hour</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">Transactions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($daily_sales['hourly_sales'] as $hour): ?>
                                    <tr>
                                        <td><?php echo date('g:i A', strtotime($hour['hour'] . ':00')); ?></td>
                                        <td class="text-end">$<?php echo number_format($hour['amount'], 2); ?></td>
                                        <td class="text-end"><?php echo $hour['count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transactions List -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Transaction Details</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Invoice #</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">GST</th>
                            <th class="text-end">Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($daily_sales['transactions'] as $sale): ?>
                            <tr>
                                <td><?php echo date('g:i A', strtotime($sale['sale_date'])); ?></td>
                                <td><?php echo 'INV-' . str_pad($sale['sale_id'], 6, '0', STR_PAD_LEFT); ?></td>
                                <td>
                                    <?php 
                                    echo $sale['company_name'] ? 
                                        htmlspecialchars($sale['company_name']) : 
                                        ($sale['customer_id'] ? 
                                            htmlspecialchars($sale['first_name'] . ' ' . $sale['last_name']) : 
                                            'Walk-in Customer'
                                        );
                                    ?>
                                </td>
                                <td class="text-center"><?php echo $sale['item_count']; ?></td>
                                <td class="text-end">$<?php echo number_format($sale['subtotal'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($sale['tax_amount'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($sale['total_amount'], 2); ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $sale['payment_method'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $sale['payment_status'] == 'paid' ? 'success' : 
                                            ($sale['payment_status'] == 'pending' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($sale['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="../../../modules/sales/view.php?id=<?php echo $sale['sale_id']; ?>" 
                                       class="btn btn-sm btn-info text-white" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="../../../modules/sales/print.php?id=<?php echo $sale['sale_id']; ?>" 
                                       class="btn btn-sm btn-secondary" title="Print" target="_blank">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>
