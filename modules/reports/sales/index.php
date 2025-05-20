<?php
session_start();
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
require_once '../../../classes/Sales.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../../login.php");
    exit();
}

// Initialize Sales class
$sales = new Sales();

// Get quick statistics
$today_sales = $sales->getTodaySales();
$month_sales = $sales->getMonthSales();
$year_sales = $sales->getYearSales();

// Include header
require_once '../../../includes/header.php';
?>

<div class="container mt-4">
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Today's Sales</h6>
                    <h3 class="card-text">$<?php echo number_format($today_sales['total'], 2); ?></h3>
                    <small><?php echo $today_sales['count']; ?> sales today</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">This Month</h6>
                    <h3 class="card-text">$<?php echo number_format($month_sales['total'], 2); ?></h3>
                    <small><?php echo $month_sales['count']; ?> sales this month</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">This Year</h6>
                    <h3 class="card-text">$<?php echo number_format($year_sales['total'], 2); ?></h3>
                    <small><?php echo $year_sales['count']; ?> sales this year</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Reports -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Sales Reports</h5>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <!-- Daily Sales Report -->
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-calendar-day"></i> Daily Sales</h5>
                            <p class="card-text">View detailed daily sales reports and transactions.</p>
                            <a href="daily_sales.php" class="btn btn-primary">View Report</a>
                        </div>
                    </div>
                </div>
                <!-- Monthly Sales Report -->
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-calendar-alt"></i> Monthly Sales</h5>
                            <p class="card-text">Analyze monthly sales performance and trends.</p>
                            <a href="monthly_sales.php" class="btn btn-primary">View Report</a>
                        </div>
                    </div>
                </div>
                <!-- Sales by Product -->
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-box"></i> Sales by Product</h5>
                            <p class="card-text">Analyze sales performance by product and category.</p>
                            <a href="sales_by_product.php" class="btn btn-primary">View Report</a>
                        </div>
                    </div>
                </div>
                <!-- Sales by Customer -->
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-users"></i> Sales by Customer</h5>
                            <p class="card-text">View sales data grouped by customer with detailed analysis.</p>
                            <a href="sales_by_customer.php" class="btn btn-primary">View Report</a>
                        </div>
                    </div>
                </div>
                <!-- Payment Analysis -->
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-credit-card"></i> Payment Analysis</h5>
                            <p class="card-text">Analyze sales by payment method and payment status.</p>
                            <a href="payment_analysis.php" class="btn btn-primary">View Report</a>
                        </div>
                    </div>
                </div>
                <!-- Outstanding Sales -->
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-clock"></i> Outstanding Sales</h5>
                            <p class="card-text">View and manage pending and outstanding payments.</p>
                            <a href="outstanding_sales.php" class="btn btn-primary">View Report</a>
                        </div>
                    </div>
                </div>
                <!-- Profit Margin -->
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-chart-line"></i> Profit Margin</h5>
                            <p class="card-text">Analyze profit margins and cost analysis.</p>
                            <a href="profit_margin.php" class="btn btn-primary">View Report</a>
                        </div>
                    </div>
                </div>
                <!-- Top Selling Items -->
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-star"></i> Top Selling Items</h5>
                            <p class="card-text">View best-selling products and performance metrics.</p>
                            <a href="top_selling.php" class="btn btn-primary">View Report</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>
