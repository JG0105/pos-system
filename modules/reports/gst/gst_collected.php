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

// Get date parameters with defaults
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t'); // Last day of current month

// Get report data
$gst_data = $reports->getGSTCollectedReport($start_date, $end_date);

// Include header
require_once '../../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-file-invoice-dollar"></i> GST Collected Report</h1>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-primary" onclick="window.print();">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="gst_collected_pdf.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                   class="btn btn-danger" target="_blank">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
                <a href="gst_collected_excel.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
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
                    <label class="form-label"> </label>
                    <button type="submit" class="btn btn-primary d-block">
                        <i class="fas fa-search"></i> Generate Report
                    </button>
                </div>
                <div class="col-md-3">
                    <label class="form-label"> </label>
                    <button type="button" class="btn btn-secondary d-block" 
                            onclick="location.href='gst_collected.php'">
                        <i class="fas fa-redo"></i> Reset Dates
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Sales</h5>
                    <h3>$<?php echo number_format($gst_data['total_sales'], 2); ?></h3>
                    <small>Including GST</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">GST Collected</h5>
                    <h3>$<?php echo number_format($gst_data['total_gst'], 2); ?></h3>
                    <small>Total GST from Sales</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Number of Sales</h5>
                    <h3><?php echo number_format($gst_data['total_transactions']); ?></h3>
                    <small>Total Transactions</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Breakdown -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Monthly GST Breakdown</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th class="text-end">Total Sales</th>
                            <th class="text-end">Sales (ex GST)</th>
                            <th class="text-end">GST Collected</th>
                            <th class="text-end">Transactions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($gst_data['monthly_breakdown'] as $month): ?>
                            <tr>
                                <td><?php echo $month['month_name']; ?></td>
                                <td class="text-end">$<?php echo number_format($month['total_sales'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($month['sales_ex_gst'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($month['gst_amount'], 2); ?></td>
                                <td class="text-end"><?php echo number_format($month['transaction_count']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th>Total</th>
                            <th class="text-end">$<?php echo number_format($gst_data['total_sales'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($gst_data['total_sales_ex_gst'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($gst_data['total_gst'], 2); ?></th>
                            <th class="text-end"><?php echo number_format($gst_data['total_transactions']); ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Detailed Transactions -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Detailed Transactions</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Invoice #</th>
                            <th>Customer</th>
                            <th class="text-end">Amount (ex GST)</th>
                            <th class="text-end">GST</th>
                            <th class="text-end">Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($gst_data['transactions'] as $transaction): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($transaction['sale_date'])); ?></td>
                                <td><?php echo 'INV-' . str_pad($transaction['sale_id'], 6, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($transaction['customer_name'] ?? 'Walk-in Customer'); ?></td>
                                <td class="text-end">$<?php echo number_format($transaction['subtotal'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($transaction['tax_amount'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($transaction['total_amount'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $transaction['payment_status'] == 'paid' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($transaction['payment_status']); ?>
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

<script>
// Add date validation
document.getElementById('start_date').addEventListener('change', function() {
    document.getElementById('end_date').min = this.value;
});

document.getElementById('end_date').addEventListener('change', function() {
    document.getElementById('start_date').max = this.value;
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
