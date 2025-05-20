<?php
session_start();
date_default_timezone_set('Australia/Adelaide');
require_once '../../../config/database.php';
require_once '../../../classes/Reports.php';
require_once '../../../includes/functions.php';

// Check if user is logged in and has admin privileges
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../../../login.php");
    exit();
}

$reports = new Reports();
$db = Database::getInstance()->getConnection();

// Get the year to process
$current_year = date('Y');
$selected_year = isset($_POST['year']) ? (int)$_POST['year'] : $current_year - 1;

// Check if form was submitted
$message = '';
$error = '';
if(isset($_POST['generate_report'])) {
    try {
        $db->beginTransaction();
        
        // 1. Generate End of Year Report
        $eoy_data = $reports->generateEndOfYearReport($selected_year);
        
        // 2. Archive the data
        $archive_success = $reports->archiveYearData($selected_year);
        
        // 3. Save report to database
        $report_id = $reports->saveEndOfYearReport($selected_year, $eoy_data);
        
        if($_POST['clear_data'] == 'yes') {
            // 4. Clear the year's data
            $clear_success = $reports->clearYearData($selected_year);
        }
        
        $db->commit();
        $message = "End of Year Report generated and processed successfully.";
        
    } catch(Exception $e) {
        $db->rollBack();
        $error = "Error processing End of Year: " . $e->getMessage();
    }
}

// Get list of available years
$stmt = $db->query("SELECT DISTINCT YEAR(sale_date) as year FROM sales ORDER BY year DESC");
$available_years = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Include header
require_once '../../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-calendar-check"></i> End of Year Processing</h1>
        </div>
        <div class="col-md-6 text-end">
            <?php if(isset($eoy_data)): ?>
                <button type="button" class="btn btn-primary" onclick="window.print();">
                    <i class="fas fa-print"></i> Print Report
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Process Selection -->
    <?php if(!isset($eoy_data)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">End of Year Processing</h5>
            </div>
            <div class="card-body">
                <form method="POST" onsubmit="return confirmProcess();">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="year" class="form-label">Select Year to Process</label>
                            <select class="form-select" id="year" name="year" required>
                                <?php foreach($available_years as $year): ?>
                                    <option value="<?php echo $year; ?>" 
                                            <?php echo $year == $selected_year ? 'selected' : ''; ?>>
                                        <?php echo $year; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="clear_data" class="form-label">Clear Year Data</label>
                            <select class="form-select" id="clear_data" name="clear_data" required>
                                <option value="no">No - Keep data in system</option>
                                <option value="yes">Yes - Archive and clear data</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"> </label>
                            <button type="submit" name="generate_report" class="btn btn-primary d-block w-100">
                                <i class="fas fa-cog"></i> Process End of Year
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php if(isset($eoy_data)): ?>
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Revenue</h5>
                        <h3>$<?php echo number_format($eoy_data['total_revenue'], 2); ?></h3>
                        <small><?php echo number_format($eoy_data['total_sales']); ?> Sales</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Expenses</h5>
                        <h3>$<?php echo number_format($eoy_data['total_expenses'], 2); ?></h3>
                        <small><?php echo number_format($eoy_data['total_purchases']); ?> Purchases</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Net Profit</h5>
                        <h3>$<?php echo number_format($eoy_data['net_profit'], 2); ?></h3>
                        <small><?php echo number_format($eoy_data['profit_margin'], 1); ?>% Margin</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">GST Position</h5>
                        <h3>$<?php echo number_format($eoy_data['net_gst'], 2); ?></h3>
                        <small>Net GST Payable</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Summary -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Financial Summary for <?php echo $selected_year; ?></h5>
            </div>
            <div class="card-body">
                <!-- Add detailed financial summary tables here -->
            </div>
        </div>

        <!-- Tax Summary -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Tax Summary</h5>
            </div>
            <div class="card-body">
                <!-- Add tax summary tables here -->
            </div>
        </div>

        <!-- Archived Data Summary -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Archived Data Summary</h5>
            </div>
            <div class="card-body">
                <!-- Add archived data summary here -->
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function confirmProcess() {
    const clearData = document.getElementById('clear_data').value;
    const year = document.getElementById('year').value;
    
    if(clearData === 'yes') {
        return confirm(
            `WARNING: This will archive and clear all data for ${year}.\n\n` +
            `This process will:\n` +
            `1. Generate the End of Year Report\n` +
            `2. Archive all ${year} data\n` +
            `3. Clear ${year} transactions from the system\n\n` +
            `Are you sure you want to continue?`
        );
    }
    return true;
}
</script>

<?php require_once '../../../includes/footer.php'; ?>
