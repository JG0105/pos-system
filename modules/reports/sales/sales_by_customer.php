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
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t'); // Last day of current month
$customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
$min_amount = isset($_GET['min_amount']) ? (float)$_GET['min_amount'] : 0;

// Get customers for filter
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT customer_id, company_name, first_name, last_name FROM customers ORDER BY company_name, first_name");
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get report data
$customer_sales = $reports->getCustomerSalesReport($start_date, $end_date, $customer_id, $min_amount);

// Include header
require_once '../../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-users"></i> Sales by Customer Report</h1>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-primary" onclick="window.print();">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="sales_by_customer_pdf.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&customer_id=<?php echo $customer_id; ?>&min_amount=<?php echo $min_amount; ?>" 
                   class="btn btn-danger" target="_blank">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
                <a href="sales_by_customer_excel.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&customer_id=<?php echo $customer_id; ?>&min_amount=<?php echo $min_amount; ?>" 
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
                <div class="col-md-3">
                    <label for="min_amount" class="form-label">Minimum Total Sales</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="min_amount" name="min_amount" 
                               value="<?php echo $min_amount; ?>" min="0" step="0.01">
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter Report
                    </button>
                    <a href="sales_by_customer.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset Filters
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
                    <h5 class="card-title">Total Sales</h5>
                    <h3>$<?php echo number_format($customer_sales['total_sales'], 2); ?></h3>
                    <small><?php echo $customer_sales['total_customers']; ?> Customers</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Average per Customer</h5>
                    <h3>$<?php echo number_format($customer_sales['average_per_customer'], 2); ?></h3>
                    <small>Customer Average</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Total GST</h5>
                    <h3>$<?php echo number_format($customer_sales['total_gst'], 2); ?></h3>
                    <small>GST Collected</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Total Transactions</h5>
                    <h3><?php echo number_format($customer_sales['total_transactions']); ?></h3>
                    <small>Number of Sales</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Sales Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Customer Sales Details</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th class="text-end">Sales</th>
                            <th class="text-end">GST</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Transactions</th>
                            <th class="text-end">Avg. Sale</th>
                            <th class="text-end">Last Sale</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($customer_sales['customers'] as $customer): ?>
                            <tr>
                                <td>
                                    <?php 
                                    echo $customer['company_name'] ? 
                                        htmlspecialchars($customer['company_name']) : 
                                        ($customer['customer_id'] ? 
                                            htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) : 
                                            'Walk-in Customer'
                                        );
                                    ?>
                                </td>
                                <td class="text-end">$<?php echo number_format($customer['sales'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($customer['gst'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($customer['total'], 2); ?></td>
                                <td class="text-end"><?php echo $customer['transactions']; ?></td>
                                <td class="text-end">$<?php echo number_format($customer['average_sale'], 2); ?></td>
                                <td class="text-end"><?php echo date('d/m/Y', strtotime($customer['last_sale'])); ?></td>
                                <td>
                                    <?php if($customer['customer_id']): ?>
                                        <a href="customer_detail.php?id=<?php echo $customer['customer_id']; ?>" 
                                           class="btn btn-sm btn-info text-white" title="View Details">
                                            <i class="fas fa-search"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th>Total</th>
                            <th class="text-end">$<?php echo number_format($customer_sales['total_sales'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($customer_sales['total_gst'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($customer_sales['total_with_gst'], 2); ?></th>
                            <th class="text-end"><?php echo $customer_sales['total_transactions']; ?></th>
                            <th class="text-end">$<?php echo number_format($customer_sales['average_per_customer'], 2); ?></th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
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
