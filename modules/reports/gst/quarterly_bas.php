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

// Get parameters
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$quarter = isset($_GET['quarter']) ? (int)$_GET['quarter'] : ceil(date('n')/3);

// Define quarter dates
$quarters = [
    1 => ['start' => "$year-07-01", 'end' => "$year-09-30", 'label' => 'July - September'],
    2 => ['start' => "$year-10-01", 'end' => "$year-12-31", 'label' => 'October - December'],
    3 => ['start' => ($year+1)."-01-01", 'end' => ($year+1)."-03-31", 'label' => 'January - March'],
    4 => ['start' => ($year+1)."-04-01", 'end' => ($year+1)."-06-30", 'label' => 'April - June']
];

// Get report data
$report_data = $reports->getBASReport($quarters[$quarter]['start'], $quarters[$quarter]['end']);

// Include header
require_once '../../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-file-invoice-dollar"></i> Quarterly BAS Report</h1>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-primary" onclick="window.print();">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="quarterly_bas_pdf.php?year=<?php echo $year; ?>&quarter=<?php echo $quarter; ?>" 
                   class="btn btn-danger" target="_blank">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
                <a href="quarterly_bas_excel.php?year=<?php echo $year; ?>&quarter=<?php echo $quarter; ?>" 
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
                    <label for="year" class="form-label">Financial Year</label>
                    <select class="form-select" id="year" name="year" onchange="this.form.submit()">
                        <?php 
                        $currentYear = date('Y');
                        for($y = $currentYear; $y >= $currentYear - 5; $y--): 
                        ?>
                            <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>>
                                <?php echo $y; ?>-<?php echo $y + 1; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="quarter" class="form-label">Quarter</label>
                    <select class="form-select" id="quarter" name="quarter" onchange="this.form.submit()">
                        <?php foreach($quarters as $q => $dates): ?>
                            <option value="<?php echo $q; ?>" <?php echo $q == $quarter ? 'selected' : ''; ?>>
                                Quarter <?php echo $q; ?> (<?php echo $dates['label']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Sales (G1)</h5>
                    <h3 class="mb-0">$<?php echo number_format($report_data['g1_total_sales'], 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">GST Collected (G11)</h5>
                    <h3 class="mb-0">$<?php echo number_format($report_data['g11_gst_collected'], 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">GST Payable (G13)</h5>
                    <h3 class="mb-0">$<?php echo number_format($report_data['g13_gst_payable'], 2); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- BAS Details -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">BAS Details</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Label</th>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">GST Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>G1</td>
                            <td>Total Sales</td>
                            <td class="text-end">$<?php echo number_format($report_data['g1_total_sales'], 2); ?></td>
                            <td class="text-end">$<?php echo number_format($report_data['g11_gst_collected'], 2); ?></td>
                        </tr>
                        <tr>
                            <td>G10</td>
                            <td>Total Purchases</td>
                            <td class="text-end">$<?php echo number_format($report_data['g10_total_purchases'], 2); ?></td>
                            <td class="text-end">$<?php echo number_format($report_data['g12_gst_paid'], 2); ?></td>
                        </tr>
                        <tr>
                            <td>G13</td>
                            <td>GST Payable</td>
                            <td colspan="2" class="text-end">
                                <strong>$<?php echo number_format($report_data['g13_gst_payable'], 2); ?></strong>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Transaction Details Tabs -->
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#sales">Sales Transactions</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#purchases">Purchase Transactions</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <!-- Sales Tab -->
                <div class="tab-pane fade show active" id="sales">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">GST</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($report_data['sales_transactions'] as $sale): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($sale['sale_date'])); ?></td>
                                        <td><?php echo 'INV-' . str_pad($sale['sale_id'], 6, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($sale['customer_name'] ?? 'Walk-in Customer'); ?></td>
                                        <td class="text-end">$<?php echo number_format($sale['subtotal'], 2); ?></td>
                                        <td class="text-end">$<?php echo number_format($sale['tax_amount'], 2); ?></td>
                                        <td class="text-end">$<?php echo number_format($sale['total_amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Purchases Tab -->
                <div class="tab-pane fade" id="purchases">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Reference #</th>
                                    <th>Supplier</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">GST</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($report_data['purchase_transactions'] as $purchase): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($purchase['purchase_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($purchase['reference_no']); ?></td>
                                        <td><?php echo htmlspecialchars($purchase['supplier_name']); ?></td>
                                        <td class="text-end">$<?php echo number_format($purchase['subtotal'], 2); ?></td>
                                        <td class="text-end">$<?php echo number_format($purchase['tax_amount'], 2); ?></td>
                                        <td class="text-end">$<?php echo number_format($purchase['total_amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>
