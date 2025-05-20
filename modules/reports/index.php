<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Include header
require_once '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-chart-bar"></i> Reports Dashboard</h1>
        </div>
    </div>

    <!-- GST/Tax Reports Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-file-invoice-dollar"></i> GST/Tax Reports
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Quarterly BAS Report -->
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Quarterly BAS Report</h5>
                                    <p class="card-text">Generate Business Activity Statement reports for ATO lodgment.</p>
                                    <a href="gst/quarterly_bas.php" class="btn btn-primary">
                                        <i class="fas fa-file-alt"></i> Generate Report
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- GST Collected Report -->
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">GST Collected Report</h5>
                                    <p class="card-text">View GST collected from sales transactions.</p>
                                    <a href="gst/gst_collected.php" class="btn btn-primary">
                                        <i class="fas fa-file-invoice"></i> View Report
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- GST Paid Report -->
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">GST Paid Report</h5>
                                    <p class="card-text">View GST paid on purchase transactions.</p>
                                    <a href="gst/gst_paid.php" class="btn btn-primary">
                                        <i class="fas fa-file-invoice"></i> View Report
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- GST Summary Report -->
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">GST Summary Report</h5>
                                    <p class="card-text">View overall GST position and summary.</p>
                                    <a href="gst/gst_summary.php" class="btn btn-primary">
                                        <i class="fas fa-chart-pie"></i> View Summary
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- GST Reconciliation Report -->
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">GST Reconciliation</h5>
                                    <p class="card-text">Reconcile GST collected vs paid.</p>
                                    <a href="gst/gst_reconciliation.php" class="btn btn-primary">
                                        <i class="fas fa-balance-scale"></i> View Reconciliation
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Coming Soon Sections -->
    <div class="row">
        <div class="col-12">
            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle"></i> More Reports Coming Soon</h5>
                <p class="mb-0">Sales Reports, Purchase Reports, Inventory Reports, and more will be added soon.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
