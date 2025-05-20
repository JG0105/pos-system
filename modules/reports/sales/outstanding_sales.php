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
$customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
$age_filter = isset($_GET['age_filter']) ? $_GET['age_filter'] : 'all'; // all, 30, 60, 90
$min_amount = isset($_GET['min_amount']) ? (float)$_GET['min_amount'] : 0;
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'due_date'; // due_date, amount, age

// Get customers for filter
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT customer_id, company_name, first_name, last_name FROM customers ORDER BY company_name, first_name");
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get report data
$outstanding_data = $reports->getOutstandingSalesReport($customer_id, $age_filter, $min_amount, $sort_by);

// Include header
require_once '../../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-clock"></i> Outstanding Sales Report</h1>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-primary" onclick="window.print();">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="outstanding_sales_pdf.php?<?php echo http_build_query($_GET); ?>" 
                   class="btn btn-danger" target="_blank">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
                <a href="outstanding_sales_excel.php?<?php echo http_build_query($_GET); ?>" 
                   class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
                <button type="button" class="btn btn-warning" onclick="sendReminders();">
                    <i class="fas fa-envelope"></i> Send Reminders
                </button>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
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
                    <label for="age_filter" class="form-label">Age of Debt</label>
                    <select class="form-select" id="age_filter" name="age_filter">
                        <option value="all" <?php echo $age_filter == 'all' ? 'selected' : ''; ?>>All Outstanding</option>
                        <option value="30" <?php echo $age_filter == '30' ? 'selected' : ''; ?>>Over 30 Days</option>
                        <option value="60" <?php echo $age_filter == '60' ? 'selected' : ''; ?>>Over 60 Days</option>
                        <option value="90" <?php echo $age_filter == '90' ? 'selected' : ''; ?>>Over 90 Days</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="min_amount" class="form-label">Minimum Amount</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="min_amount" name="min_amount" 
                               value="<?php echo $min_amount; ?>" min="0" step="0.01">
                    </div>
                </div>
                <div class="col-md-2">
                    <label for="sort_by" class="form-label">Sort By</label>
                    <select class="form-select" id="sort_by" name="sort_by">
                        <option value="due_date" <?php echo $sort_by == 'due_date' ? 'selected' : ''; ?>>Due Date</option>
                        <option value="amount" <?php echo $sort_by == 'amount' ? 'selected' : ''; ?>>Amount</option>
                        <option value="age" <?php echo $sort_by == 'age' ? 'selected' : ''; ?>>Age</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"> </label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
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
                    <h5 class="card-title">Total Outstanding</h5>
                    <h3>$<?php echo number_format($outstanding_data['total_outstanding'], 2); ?></h3>
                    <small><?php echo $outstanding_data['total_invoices']; ?> Invoices</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">30+ Days</h5>
                    <h3>$<?php echo number_format($outstanding_data['aged_30'], 2); ?></h3>
                    <small><?php echo $outstanding_data['aged_30_count']; ?> Invoices</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">90+ Days</h5>
                    <h3>$<?php echo number_format($outstanding_data['aged_90'], 2); ?></h3>
                    <small><?php echo $outstanding_data['aged_90_count']; ?> Invoices</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Average Days Outstanding</h5>
                    <h3><?php echo number_format($outstanding_data['average_days'], 0); ?></h3>
                    <small>Days</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Outstanding Invoices Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Outstanding Invoices</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th>Invoice #</th>
                            <th>Customer</th>
                            <th>Invoice Date</th>
                            <th>Due Date</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Balance</th>
                            <th>Age (Days)</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($outstanding_data['invoices'] as $invoice): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input invoice-select" 
                                           value="<?php echo $invoice['sale_id']; ?>">
                                </td>
                                <td><?php echo 'INV-' . str_pad($invoice['sale_id'], 6, '0', STR_PAD_LEFT); ?></td>
                                <td>
                                    <?php 
                                    echo $invoice['company_name'] ? 
                                        htmlspecialchars($invoice['company_name']) : 
                                        htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']); 
                                    ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($invoice['sale_date'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($invoice['due_date'])); ?></td>
                                <td class="text-end">$<?php echo number_format($invoice['total_amount'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($invoice['amount_paid'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($invoice['balance'], 2); ?></td>
                                <td><?php echo $invoice['days_overdue']; ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $invoice['days_overdue'] > 90 ? 'danger' : 
                                            ($invoice['days_overdue'] > 60 ? 'warning' : 
                                                ($invoice['days_overdue'] > 30 ? 'info' : 'success')); 
                                    ?>">
                                        <?php 
                                        echo $invoice['days_overdue'] > 90 ? 'Critical' : 
                                            ($invoice['days_overdue'] > 60 ? 'Overdue' : 
                                                ($invoice['days_overdue'] > 30 ? 'Due Soon' : 'Current')); 
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="../../../modules/sales/view.php?id=<?php echo $invoice['sale_id']; ?>" 
                                           class="btn btn-sm btn-info text-white" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="../../../modules/sales/record_payment.php?id=<?php echo $invoice['sale_id']; ?>" 
                                           class="btn btn-sm btn-success" title="Record Payment">
                                            <i class="fas fa-dollar-sign"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-warning" 
                                                onclick="sendReminder(<?php echo $invoice['sale_id']; ?>)"
                                                title="Send Reminder">
                                            <i class="fas fa-envelope"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <td colspan="5"><strong>Total Outstanding</strong></td>
                            <td class="text-end"><strong>$<?php echo number_format($outstanding_data['total_amount'], 2); ?></strong></td>
                            <td class="text-end"><strong>$<?php echo number_format($outstanding_data['total_paid'], 2); ?></strong></td>
                            <td class="text-end"><strong>$<?php echo number_format($outstanding_data['total_outstanding'], 2); ?></strong></td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Select all checkboxes
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.invoice-select').forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Send reminder for single invoice
function sendReminder(invoiceId) {
    if(confirm('Send payment reminder for this invoice?')) {
        // Add your reminder sending logic here
        alert('Reminder sent successfully');
    }
}

// Send reminders for selected invoices
function sendReminders() {
    const selected = Array.from(document.querySelectorAll('.invoice-select:checked'))
                         .map(checkbox => checkbox.value);
    
    if(selected.length === 0) {
        alert('Please select at least one invoice');
        return;
    }

    if(confirm('Send payment reminders for ' + selected.length + ' invoice(s)?')) {
        // Add your bulk reminder sending logic here
        alert('Reminders sent successfully');
    }
}

// Add date validation
document.getElementById('start_date').addEventListener('change', function() {
    document.getElementById('end_date').min = this.value;
});

document.getElementById('end_date').addEventListener('change', function() {
    document.getElementById('start_date').max = this.value;
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
