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
$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;
$age_filter = isset($_GET['age_filter']) ? $_GET['age_filter'] : 'all'; // all, 30, 60, 90
$min_amount = isset($_GET['min_amount']) ? (float)$_GET['min_amount'] : 0;
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'due_date'; // due_date, amount, age

// Get suppliers for filter
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT supplier_id, company_name FROM suppliers ORDER BY company_name");
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get report data
$payment_data = $reports->getOutstandingPaymentsReport($supplier_id, $age_filter, $min_amount, $sort_by);

// Include header
require_once '../../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-dollar-sign"></i> Outstanding Payments</h1>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-primary" onclick="window.print();">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="outstanding_payments_pdf.php?<?php echo http_build_query($_GET); ?>" 
                   class="btn btn-danger" target="_blank">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
                <a href="outstanding_payments_excel.php?<?php echo http_build_query($_GET); ?>" 
                   class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
                <button type="button" class="btn btn-info" onclick="schedulePayments();">
                    <i class="fas fa-calendar"></i> Schedule Payments
                </button>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
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
                    <h3>$<?php echo number_format($payment_data['total_outstanding'], 2); ?></h3>
                    <small><?php echo $payment_data['total_invoices']; ?> Invoices</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">30+ Days</h5>
                    <h3>$<?php echo number_format($payment_data['aged_30'], 2); ?></h3>
                    <small><?php echo $payment_data['aged_30_count']; ?> Invoices</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">90+ Days</h5>
                    <h3>$<?php echo number_format($payment_data['aged_90'], 2); ?></h3>
                    <small><?php echo $payment_data['aged_90_count']; ?> Invoices</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Due This Week</h5>
                    <h3>$<?php echo number_format($payment_data['due_this_week'], 2); ?></h3>
                    <small><?php echo $payment_data['due_this_week_count']; ?> Invoices</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Aging Summary -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payment Aging Summary</h5>
                </div>
                <div class="card-body">
                    <canvas id="agingChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Top Suppliers with Outstanding</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Supplier</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($payment_data['top_suppliers'] as $supplier): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($supplier['company_name']); ?></td>
                                        <td class="text-end">$<?php echo number_format($supplier['amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
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
                            <th>Supplier</th>
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
                        <?php foreach($payment_data['invoices'] as $invoice): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input invoice-select" 
                                           value="<?php echo $invoice['invoice_id']; ?>">
                                </td>
                                <td><?php echo htmlspecialchars($invoice['reference_no']); ?></td>
                                <td><?php echo htmlspecialchars($invoice['supplier_name']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($invoice['invoice_date'])); ?></td>
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
                                        <a href="../../../modules/supplier-invoices/view.php?id=<?php echo $invoice['invoice_id']; ?>" 
                                           class="btn btn-sm btn-info text-white" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="../../../modules/supplier-invoices/record_payment.php?id=<?php echo $invoice['invoice_id']; ?>" 
                                           class="btn btn-sm btn-success" title="Record Payment">
                                            <i class="fas fa-dollar-sign"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <td colspan="5"><strong>Total Outstanding</strong></td>
                            <td class="text-end"><strong>$<?php echo number_format($payment_data['total_amount'], 2); ?></strong></td>
                            <td class="text-end"><strong>$<?php echo number_format($payment_data['total_paid'], 2); ?></strong></td>
                            <td class="text-end"><strong>$<?php echo number_format($payment_data['total_outstanding'], 2); ?></strong></td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Select all checkboxes
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.invoice-select').forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Schedule payments function
function schedulePayments() {
    const selected = Array.from(document.querySelectorAll('.invoice-select:checked'))
                         .map(checkbox => checkbox.value);
    
    if(selected.length === 0) {
        alert('Please select at least one invoice');
        return;
    }

    // Redirect to payment scheduling page with selected invoices
    window.location.href = 'schedule_payments.php?invoices=' + selected.join(',');
}

// Create aging chart
const ctx = document.getElementById('agingChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Current', '1-30 Days', '31-60 Days', '61-90 Days', 'Over 90 Days'],
        datasets: [{
            label: 'Outstanding Amount',
            data: [
                <?php echo $payment_data['current']; ?>,
                <?php echo $payment_data['aged_30']; ?>,
                <?php echo $payment_data['aged_60']; ?>,
                <?php echo $payment_data['aged_90']; ?>,
                <?php echo $payment_data['aged_90_plus']; ?>
            ],
            backgroundColor: [
                'rgba(40, 167, 69, 0.5)',  // green
                'rgba(23, 162, 184, 0.5)', // cyan
                'rgba(255, 193, 7, 0.5)',  // yellow
                'rgba(253, 126, 20, 0.5)', // orange
                'rgba(220, 53, 69, 0.5)'   // red
            ],
            borderColor: [
                'rgb(40, 167, 69)',
                'rgb(23, 162, 184)',
                'rgb(255, 193, 7)',
                'rgb(253, 126, 20)',
                'rgb(220, 53, 69)'
            ],
            borderWidth: 1
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
</script>

<?php require_once '../../../includes/footer.php'; ?>
