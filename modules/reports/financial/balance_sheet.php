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
$as_of_date = isset($_GET['as_of_date']) ? $_GET['as_of_date'] : date('Y-m-d');
$comparison = isset($_GET['comparison']) ? $_GET['comparison'] : 'previous'; // previous, year, custom
$comparison_date = isset($_GET['comparison_date']) ? $_GET['comparison_date'] : date('Y-m-d', strtotime('-1 year'));

// Get report data
$balance_sheet_data = $reports->getBalanceSheetReport($as_of_date, $comparison_date);

// Include header
require_once '../../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-balance-scale"></i> Balance Sheet</h1>
        </div>
        <div class="col-md-6 text-end">
            <button type="button" class="btn btn-primary" onclick="window.print();">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="as_of_date" class="form-label">As of Date</label>
                    <input type="date" class="form-control" id="as_of_date" name="as_of_date" 
                           value="<?php echo $as_of_date; ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-3">
                    <label for="comparison" class="form-label">Compare To</label>
                    <select class="form-select" id="comparison" name="comparison">
                        <option value="previous" <?php echo $comparison == 'previous' ? 'selected' : ''; ?>>Previous Month</option>
                        <option value="year" <?php echo $comparison == 'year' ? 'selected' : ''; ?>>Previous Year</option>
                        <option value="custom" <?php echo $comparison == 'custom' ? 'selected' : ''; ?>>Custom Date</option>
                    </select>
                </div>
                <div class="col-md-3 comparison-date-container" style="display: <?php echo $comparison == 'custom' ? 'block' : 'none'; ?>">
                    <label for="comparison_date" class="form-label">Comparison Date</label>
                    <input type="date" class="form-control" id="comparison_date" name="comparison_date" 
                           value="<?php echo $comparison_date; ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label"> </label>
                    <button type="submit" class="btn btn-primary d-block w-100">
                        <i class="fas fa-sync"></i> Update
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
                    <h5 class="card-title">Total Assets</h5>
                    <h3>$<?php echo number_format($balance_sheet_data['total_assets'], 2); ?></h3>
                    <small><?php echo $balance_sheet_data['assets_change']; ?>% vs Comparison</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Liabilities</h5>
                    <h3>$<?php echo number_format($balance_sheet_data['total_liabilities'], 2); ?></h3>
                    <small><?php echo $balance_sheet_data['liabilities_change']; ?>% vs Comparison</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Equity</h5>
                    <h3>$<?php echo number_format($balance_sheet_data['total_equity'], 2); ?></h3>
                    <small><?php echo $balance_sheet_data['equity_change']; ?>% vs Comparison</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Working Capital</h5>
                    <h3>$<?php echo number_format($balance_sheet_data['working_capital'], 2); ?></h3>
                    <small>Current Assets - Current Liabilities</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Ratios -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Financial Ratios</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="ratio-box text-center p-3">
                                <h6>Current Ratio</h6>
                                <h4><?php echo number_format($balance_sheet_data['current_ratio'], 2); ?></h4>
                                <small class="text-muted">Current Assets / Current Liabilities</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="ratio-box text-center p-3">
                                <h6>Quick Ratio</h6>
                                <h4><?php echo number_format($balance_sheet_data['quick_ratio'], 2); ?></h4>
                                <small class="text-muted">(Current Assets - Inventory) / Current Liabilities</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="ratio-box text-center p-3">
                                <h6>Debt to Equity</h6>
                                <h4><?php echo number_format($balance_sheet_data['debt_to_equity'], 2); ?></h4>
                                <small class="text-muted">Total Liabilities / Total Equity</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="ratio-box text-center p-3">
                                <h6>Asset Turnover</h6>
                                <h4><?php echo number_format($balance_sheet_data['asset_turnover'], 2); ?></h4>
                                <small class="text-muted">Sales / Average Total Assets</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Balance Sheet -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Balance Sheet as of <?php echo date('d/m/Y', strtotime($as_of_date)); ?></h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Account</th>
                            <th class="text-end">Current</th>
                            <th class="text-end">Comparison</th>
                            <th class="text-end">Variance</th>
                            <th class="text-end">Variance %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Assets Section -->
                        <tr class="table-primary">
                            <th colspan="5">Assets</th>
                        </tr>
                        <tr class="table-secondary">
                            <th colspan="5">Current Assets</th>
                        </tr>
                        <?php foreach($balance_sheet_data['current_assets'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td class="text-end">$<?php echo number_format($item['current'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($item['comparison'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($item['variance'], 2); ?></td>
                                <td class="text-end">
                                    <span class="text-<?php echo $item['variance_percent'] >= 0 ? 'success' : 'danger'; ?>">
                                        <?php echo ($item['variance_percent'] >= 0 ? '+' : '') . 
                                              number_format($item['variance_percent'], 1); ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="table-success">
                            <th>Total Current Assets</th>
                            <th class="text-end">$<?php echo number_format($balance_sheet_data['total_current_assets'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($balance_sheet_data['comparison_current_assets'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($balance_sheet_data['current_assets_variance'], 2); ?></th>
                            <th class="text-end">
                                <span class="text-<?php echo $balance_sheet_data['current_assets_change'] >= 0 ? 'success' : 'danger'; ?>">
                                    <?php echo ($balance_sheet_data['current_assets_change'] >= 0 ? '+' : '') . 
                                          number_format($balance_sheet_data['current_assets_change'], 1); ?>%
                                </span>
                            </th>
                        </tr>

                        <tr class="table-secondary">
                            <th colspan="5">Non-Current Assets</th>
                        </tr>
                        <?php foreach($balance_sheet_data['non_current_assets'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td class="text-end">$<?php echo number_format($item['current'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($item['comparison'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($item['variance'], 2); ?></td>
                                <td class="text-end">
                                    <span class="text-<?php echo $item['variance_percent'] >= 0 ? 'success' : 'danger'; ?>">
                                        <?php echo ($item['variance_percent'] >= 0 ? '+' : '') . 
                                              number_format($item['variance_percent'], 1); ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="table-success">
                            <th>Total Non-Current Assets</th>
                            <th class="text-end">$<?php echo number_format($balance_sheet_data['total_non_current_assets'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($balance_sheet_data['comparison_non_current_assets'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($balance_sheet_data['non_current_assets_variance'], 2); ?></th>
                            <th class="text-end">
                                <span class="text-<?php echo $balance_sheet_data['non_current_assets_change'] >= 0 ? 'success' : 'danger'; ?>">
                                    <?php echo ($balance_sheet_data['non_current_assets_change'] >= 0 ? '+' : '') . 
                                          number_format($balance_sheet_data['non_current_assets_change'], 1); ?>%
                                </span>
                            </th>
                        </tr>

                        <tr class="table-primary">
                            <th>Total Assets</th>
                            <th class="text-end">$<?php echo number_format($balance_sheet_data['total_assets'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($balance_sheet_data['comparison_total_assets'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($balance_sheet_data['total_assets_variance'], 2); ?></th>
                            <th class="text-end">
                                <span class="text-<?php echo $balance_sheet_data['assets_change'] >= 0 ? 'success' : 'danger'; ?>">
                                    <?php echo ($balance_sheet_data['assets_change'] >= 0 ? '+' : '') . 
                                          number_format($balance_sheet_data['assets_change'], 1); ?>%
                                </span>
                            </th>
                        </tr>

                        <!-- Liabilities Section -->
                        <tr class="table-primary">
                            <th colspan="5">Liabilities</th>
                        </tr>
                        <tr class="table-secondary">
                            <th colspan="5">Current Liabilities</th>
                        </tr>
                        <?php foreach($balance_sheet_data['current_liabilities'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td class="text-end">$<?php echo number_format($item['current'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($item['comparison'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($item['variance'], 2); ?></td>
                                <td class="text-end">
                                    <span class="text-<?php echo $item['variance_percent'] <= 0 ? 'success' : 'danger'; ?>">
                                        <?php echo ($item['variance_percent'] >= 0 ? '+' : '') . 
                                              number_format($item['variance_percent'], 1); ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="table-danger">
                            <th>Total Current Liabilities</th>
                            <th class="text-end">$<?php echo number_format($balance_sheet_data['total_current_liabilities'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($balance_sheet_data['comparison_current_liabilities'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($balance_sheet_data['current_liabilities_variance'], 2); ?></th>
                            <th class="text-end">
                                <span class="text-<?php echo $balance_sheet_data['current_liabilities_change'] <= 0 ? 'success' : 'danger'; ?>">
                                    <?php echo ($balance_sheet_data['current_liabilities_change'] >= 0 ? '+' : '') . 
                                          number_format($balance_sheet_data['current_liabilities_change'], 1); ?>%
                                </span>
                            </th>
                        </tr>

                        <tr class="table-secondary">
                            <th colspan="5">Non-Current Liabilities</th>
                        </tr>
                        <?php foreach($balance_sheet_data['non_current_liabilities'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td class="text-end">$<?php echo number_format($item['current'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($item['comparison'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($item['variance'], 2); ?></td>
                                <td class="text-end">
                                    <span class="text-<?php echo $item['variance_percent'] <= 0 ? 'success' : 'danger'; ?>">
                                        <?php echo ($item['variance_percent'] >= 0 ? '+' : '') . 
                                              number_format($item['variance_percent'], 1); ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="table-danger">
                            <th>Total Non-Current Liabilities</th>
                            <th class="text-end">$<?php echo number_format($balance_sheet_data['total_non_current_liabilities'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($balance_sheet_data['comparison_non_current_liabilities'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($balance_sheet_data['non_current_liabilities_variance'], 2); ?></th>
                            <th class="text-end">
                                <span class="text-<?php echo $balance_sheet_data['non_current_liabilities_change'] <= 0 ? 'success' : 'danger'; ?>">
                                    <?php echo ($balance_sheet_data['non_current_liabilities_change'] >= 0 ? '+' : '') . 
                                          number_format($balance_sheet_data['non_current_liabilities_change'], 1); ?>%
                                </span>
                            </th>
                        </tr>

                        <tr class="table-primary">
                            <th>Total Liabilities</th>
                            <th class="text-end">$<?php echo number_format($balance_sheet_data['total_liabilities'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($balance_sheet_data['comparison_total_liabilities'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($balance_sheet_data['total_liabilities_variance'], 2); ?></th>
                            <th class="text-end">
                                <span class="text-<?php echo $balance_sheet_data['liabilities_change'] <= 0 ? 'success' : 'danger'; ?>">
                                    <?php echo ($balance_sheet_data['liabilities_change'] >= 0 ? '+' : '') . 
                                          number_format($balance_sheet_data['liabilities_change'], 1); ?>%
                                </span>
                            </th>
                        </tr>

                        <!-- Equity Section -->
                        <tr class="table-primary">
                            <th colspan="5">Equity</th>
                        </tr>
                        <?php foreach($balance_sheet_data['equity_items'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td class="text-end">$<?php echo number_format($item['current'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($item['comparison'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($item['variance'], 2); ?></td>
                                <td class="text-end">
                                    <span class="text-<?php echo $item['variance_percent'] >= 0 ? 'success' : 'danger'; ?>">
                                        <?php echo ($item['variance_percent'] >= 0 ? '+' : '') . 
                                              number_format($item['variance_percent'], 1); ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="table-success">
                            <th>Total Equity</th>
                            <th class="text-end">$<?php echo number_format($balance_sheet_data['total_equity'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($balance_sheet_data['comparison_total_equity'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($balance_sheet_data['total_equity_variance'], 2); ?></th>
                            <th class="text-end">
                                <span class="text-<?php echo $balance_sheet_data['equity_change'] >= 0 ? 'success' : 'danger'; ?>">
                                    <?php echo ($balance_sheet_data['equity_change'] >= 0 ? '+' : '') . 
                                          number_format($balance_sheet_data['equity_change'], 1); ?>%
                                </span>
                            </th>
                        </tr>

                        <!-- Total Liabilities and Equity -->
                        <tr class="table-primary">
                            <th>Total Liabilities and Equity</th>
                            <th class="text-end">$<?php echo number_format($balance_sheet_data['total_liabilities_equity'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($balance_sheet_data['comparison_total_liabilities_equity'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($balance_sheet_data['total_liabilities_equity_variance'], 2); ?></th>
                            <th class="text-end">
                                <span class="text-<?php echo $balance_sheet_data['liabilities_equity_change'] >= 0 ? 'success' : 'danger'; ?>">
                                    <?php echo ($balance_sheet_data['liabilities_equity_change'] >= 0 ? '+' : '') . 
                                          number_format($balance_sheet_data['liabilities_equity_change'], 1); ?>%
                                </span>
                            </th>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Show/hide comparison date based on comparison type
document.getElementById('comparison').addEventListener('change', function() {
    const comparisonDateContainer = document.querySelector('.comparison-date-container');
    comparisonDateContainer.style.display = this.value === 'custom' ? 'block' : 'none';
});

// Validate comparison date is before as of date
document.getElementById('comparison_date').addEventListener('change', function() {
    const asOfDate = document.getElementById('as_of_date').value;
    if(this.value >= asOfDate) {
        alert('Comparison date must be before the as of date');
        this.value = '';
    }
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
