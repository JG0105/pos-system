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
$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;
$payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
$min_amount = isset($_GET['min_amount']) ? (float)$_GET['min_amount'] : 0;

// Get suppliers for filter
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT supplier_id, company_name FROM suppliers ORDER BY company_name");
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get report data
$payment_data = $reports->getSupplierPaymentHistory($start_date, $end_date, $supplier_id, $payment_method, $min_amount);

// Include header
require_once '../../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-history"></i> Supplier Payment History</h1>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-primary" onclick="window.print();">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="supplier_payment_history_pdf.php?<?php echo http_build_query($_GET); ?>" 
                   class="btn btn-danger" target="_blank">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
                <a href="supplier_payment_history_excel.php?<?php echo http_build_query($_GET); ?>" 
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
                <div class="col-md-2">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-2">
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
                <div class="col-md-2">
                    <label for="payment_method" class="form-label">Payment Method</label>
                    <select class="form-select" id="payment_method" name="payment_method">
                        <option value="">All Methods</option>
                        <option value="bank_transfer" <?php echo $payment_method == 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                        <option value="cheque" <?php echo $payment_method == 'cheque' ? 'selected' : ''; ?>>Cheque</option>
                        <option value="credit_card" <?php echo $payment_method == 'credit_card' ? 'selected' : ''; ?>>Credit Card</option>
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
                <div class="col-md-1">
                    <label class="form-label"> </label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
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
                    <h5 class="card-title">Total Payments</h5>
                    <h3>$<?php echo number_format($payment_data['total_amount'], 2); ?></h3>
                    <small><?php echo number_format($payment_data['total_payments']); ?> Payments</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Average Payment</h5>
                    <h3>$<?php echo number_format($payment_data['average_payment'], 2); ?></h3>
                    <small>Per Transaction</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Suppliers Paid</h5>
                    <h3><?php echo number_format($payment_data['suppliers_paid']); ?></h3>
                    <small>Unique Suppliers</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Largest Payment</h5>
                    <h3>$<?php echo number_format($payment_data['largest_payment'], 2); ?></h3>
                    <small><?php echo htmlspecialchars($payment_data['largest_supplier']); ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Charts -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Monthly Payment Trend</h5>
                </div>
                <div class="card-body">
                    <canvas id="trendChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payment Method Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="methodChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Payment Details</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Supplier</th>
                            <th>Invoice #</th>
                            <th class="text-end">Amount</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th>Recorded By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($payment_data['payments'] as $payment): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?></td>
                                <td><?php echo htmlspecialchars($payment['reference_no']); ?></td>
                                <td><?php echo htmlspecialchars($payment['supplier_name']); ?></td>
                                <td><?php echo htmlspecialchars($payment['invoice_no']); ?></td>
                                <td class="text-end">$<?php echo number_format($payment['amount'], 2); ?></td>
                                <td><?php echo ucwords(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $payment['status'] == 'completed' ? 'success' : 
                                            ($payment['status'] == 'pending' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($payment['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($payment['recorded_by']); ?></td>
                                <td>
                                    <a href="../../../modules/supplier-payments/view.php?id=<?php echo $payment['payment_id']; ?>" 
                                       class="btn btn-sm btn-info text-white" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if($payment['status'] != 'completed'): ?>
                                        <a href="../../../modules/supplier-payments/edit.php?id=<?php echo $payment['payment_id']; ?>" 
                                           class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <td colspan="4"><strong>Total</strong></td>
                            <td class="text-end"><strong>$<?php echo number_format($payment_data['total_amount'], 2); ?></strong></td>
                            <td colspan="4"></td>
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
// Create trend chart
const trendCtx = document.getElementById('trendChart').getContext('2d');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($payment_data['trend_labels']); ?>,
        datasets: [{
            label: 'Payment Amount',
            data: <?php echo json_encode($payment_data['trend_values']); ?>,
            borderColor: 'rgb(54, 162, 235)',
            tension: 0.1
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

// Create payment method chart
const methodCtx = document.getElementById('methodChart').getContext('2d');
new Chart(methodCtx, {
    type: 'pie',
    data: {
        labels: ['Bank Transfer', 'Cheque', 'Credit Card'],
        datasets: [{
            data: [
                <?php echo $payment_data['method_bank_transfer']; ?>,
                <?php echo $payment_data['method_cheque']; ?>,
                <?php echo $payment_data['method_credit_card']; ?>
            ],
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
                        return context.label + ': $' + context.raw.toLocaleString();
                    }
                }
            }
        }
    }
});

// Add date validation
document.getElementById('start_date').addEventListener('change', function() {
    document.getElementById('end_date').min = this.value;
});

document.getElementById('end_date').addEventListener('change', function() {
    document.getElementById('start_date').max = this.value;
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
