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
$reconciliation_data = $reports->getGSTReconciliationReport($start_date, $end_date);

// Include header
require_once '../../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-balance-scale"></i> GST Reconciliation Report</h1>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-primary" onclick="window.print();">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="gst_reconciliation_pdf.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                   class="btn btn-danger" target="_blank">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
                <a href="gst_reconciliation_excel.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
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
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="<?php echo $end_date; ?>">
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
                    <h5 class="card-title">Expected GST</h5>
                    <h3>$<?php echo number_format($reconciliation_data['expected_gst'], 2); ?></h3>
                    <small>Based on Sales & Purchases</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Recorded GST</h5>
                    <h3>$<?php echo number_format($reconciliation_data['recorded_gst'], 2); ?></h3>
                    <small>From Transactions</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card <?php echo $reconciliation_data['variance'] == 0 ? 'bg-success' : 'bg-danger'; ?> text-white">
                <div class="card-body">
                    <h5 class="card-title">Variance</h5>
                    <h3>$<?php echo number_format(abs($reconciliation_data['variance']), 2); ?></h3>
                    <small><?php echo $reconciliation_data['variance'] == 0 ? 'No Discrepancy' : 'Requires Review'; ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <h5 class="card-title">Transactions Checked</h5>
                    <h3><?php echo number_format($reconciliation_data['total_transactions']); ?></h3>
                    <small>Total Records Reviewed</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Reconciliation Details -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">GST Reconciliation Details</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th class="text-end">Expected GST</th>
                            <th class="text-end">Recorded GST</th>
                            <th class="text-end">Variance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Sales GST -->
                        <tr>
                            <td><strong>Sales GST</strong></td>
                            <td class="text-end">$<?php echo number_format($reconciliation_data['sales']['expected'], 2); ?></td>
                            <td class="text-end">$<?php echo number_format($reconciliation_data['sales']['recorded'], 2); ?></td>
                            <td class="text-end">$<?php echo number_format(abs($reconciliation_data['sales']['variance']), 2); ?></td>
                            <td>
                                <?php if($reconciliation_data['sales']['variance'] == 0): ?>
                                    <span class="badge bg-success">Matched</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Discrepancy</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <!-- Purchases GST -->
                        <tr>
                            <td><strong>Purchases GST</strong></td>
                            <td class="text-end">$<?php echo number_format($reconciliation_data['purchases']['expected'], 2); ?></td>
                            <td class="text-end">$<?php echo number_format($reconciliation_data['purchases']['recorded'], 2); ?></td>
                            <td class="text-end">$<?php echo number_format(abs($reconciliation_data['purchases']['variance']), 2); ?></td>
                            <td>
                                <?php if($reconciliation_data['purchases']['variance'] == 0): ?>
                                    <span class="badge bg-success">Matched</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Discrepancy</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th>Net Position</th>
                            <th class="text-end">$<?php echo number_format($reconciliation_data['expected_gst'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($reconciliation_data['recorded_gst'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format(abs($reconciliation_data['variance']), 2); ?></th>
                            <th>
                                <?php if($reconciliation_data['variance'] == 0): ?>
                                    <span class="badge bg-success">Reconciled</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Unreconciled</span>
                                <?php endif; ?>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Discrepancy Details -->
    <?php if(!empty($reconciliation_data['discrepancies'])): ?>
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Discrepancy Details</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th class="text-end">Expected GST</th>
                            <th class="text-end">Recorded GST</th>
                            <th class="text-end">Variance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($reconciliation_data['discrepancies'] as $discrepancy): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($discrepancy['date'])); ?></td>
                                <td><?php echo htmlspecialchars($discrepancy['reference']); ?></td>
                                <td><?php echo ucfirst($discrepancy['type']); ?></td>
                                <td><?php echo htmlspecialchars($discrepancy['description']); ?></td>
                                <td class="text-end">$<?php echo number_format($discrepancy['expected_gst'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($discrepancy['recorded_gst'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format(abs($discrepancy['variance']), 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
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
