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
$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;

// Get suppliers for filter
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT supplier_id, company_name FROM suppliers ORDER BY company_name");
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get report data
$gst_data = $reports->getGSTPaidReport($start_date, $end_date, $supplier_id);

// Include header
require_once '../../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-file-invoice-dollar"></i> GST Paid Report</h1>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-primary" onclick="window.print();">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="gst_paid_pdf.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&supplier_id=<?php echo $supplier_id; ?>" 
                   class="btn btn-danger" target="_blank">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
                <a href="gst_paid_excel.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&supplier_id=<?php echo $supplier_id; ?>" 
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
                <div class="col-md-3">
                    <label class="form-label"> </label>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Generate Report
                        </button>
                        <button type="button" class="btn btn-secondary" 
                                onclick="location.href='gst_paid.php'">
                            <i class="fas fa-redo"></i> Reset Filters
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Purchases</h5>
                    <h3>$<?php echo number_format($gst_data['total_purchases'], 2); ?></h3>
                    <small>Including GST</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">GST Paid</h5>
                    <h3>$<?php echo number_format($gst_data['total_gst'], 2); ?></h3>
                    <small>Total GST on Purchases</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Number of Purchases</h5>
                    <h3><?php echo number_format($gst_data['total_transactions']); ?></h3>
                    <small>Total Transactions</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Supplier Breakdown -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">GST by Supplier</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Supplier</th>
                            <th class="text-end">Total Purchases</th>
                            <th class="text-end">Purchases (ex GST)</th>
                            <th class="text-end">GST Paid</th>
                            <th class="text-end">Transactions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($gst_data['supplier_breakdown'] as $supplier): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($supplier['supplier_name']); ?></td>
                                <td class="text-end">$<?php echo number_format($supplier['total_purchases'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($supplier['purchases_ex_gst'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($supplier['gst_amount'], 2); ?></td>
                                <td class="text-end"><?php echo number_format($supplier['transaction_count']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th>Total</th>
                            <th class="text-end">$<?php echo number_format($gst_data['total_purchases'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($gst_data['total_purchases_ex_gst'], 2); ?></th>
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
                            <th>Reference #</th>
                            <th>Supplier</th>
                            <th class="text-end">Amount (ex GST)</th>
                            <th class="text-end">GST</th>
                            <th class="text-end">Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($gst_data['transactions'] as $transaction): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($transaction['purchase_date'])); ?></td>
                                <td><?php echo htmlspecialchars($transaction['reference_no']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['supplier_name']); ?></td>
                                <td class="text-end">$<?php echo number_format($transaction['subtotal'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($transaction['tax_amount'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($transaction['total_amount'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $transaction['status'] == 'paid' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($transaction['status']); ?>
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
