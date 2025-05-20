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
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');

// Get report data
$gst_data = $reports->getGSTSummaryReport($year, $month);

// Include header
require_once '../../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-chart-pie"></i> GST Summary Report</h1>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-primary" onclick="window.print();">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="gst_summary_pdf.php?year=<?php echo $year; ?>&month=<?php echo $month; ?>" 
                   class="btn btn-danger" target="_blank">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
                <a href="gst_summary_excel.php?year=<?php echo $year; ?>&month=<?php echo $month; ?>" 
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
                    <h5 class="card-title">GST Collected</h5>
                    <h3>$<?php echo number_format($gst_data['gst_collected'], 2); ?></h3>
                    <small>From Sales</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">GST Paid</h5>
                    <h3>$<?php echo number_format($gst_data['gst_paid'], 2); ?></h3>
                    <small>On Purchases</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card <?php echo $gst_data['net_gst'] >= 0 ? 'bg-success' : 'bg-danger'; ?> text-white">
                <div class="card-body">
                    <h5 class="card-title">Net GST Position</h5>
                    <h3>$<?php echo number_format(abs($gst_data['net_gst']), 2); ?></h3>
                    <small><?php echo $gst_data['net_gst'] >= 0 ? 'Payable' : 'Credit'; ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Transactions</h5>
                    <h3><?php echo number_format($gst_data['total_transactions']); ?></h3>
                    <small>Sales & Purchases</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Trend -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Monthly GST Trend</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th class="text-end">GST Collected</th>
                            <th class="text-end">GST Paid</th>
                            <th class="text-end">Net Position</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($gst_data['monthly_trend'] as $trend): ?>
                            <tr>
                                <td><?php echo $trend['month_name']; ?></td>
                                <td class="text-end">$<?php echo number_format($trend['gst_collected'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($trend['gst_paid'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format(abs($trend['net_position']), 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $trend['net_position'] >= 0 ? 'success' : 'danger'; ?>">
                                        <?php echo $trend['net_position'] >= 0 ? 'Payable' : 'Credit'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Detailed Breakdown -->
    <div class="row">
        <!-- Sales GST -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">GST Collected from Sales</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($gst_data['sales_breakdown'] as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td class="text-end">$<?php echo number_format($category['gst_amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-primary">
                                    <th>Total GST Collected</th>
                                    <th class="text-end">$<?php echo number_format($gst_data['gst_collected'], 2); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchases GST -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">GST Paid on Purchases</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($gst_data['purchases_breakdown'] as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td class="text-end">$<?php echo number_format($category['gst_amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-info">
                                    <th>Total GST Paid</th>
                                    <th class="text-end">$<?php echo number_format($gst_data['gst_paid'], 2); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Add form submit handler to update report
document.querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault();
    const year = document.getElementById('year').value;
    const month = document.getElementById('month').value;
    window.location.href = `gst_summary.php?year=${year}&month=${month}`;
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
