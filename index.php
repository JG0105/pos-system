<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';
require_once 'includes/header.php';

// Initialize variables with defaults
$activeProducts = 0;
$lowStockCount = 0;
$customerCount = 0;
$salesData = ['count' => 0, 'total' => 0];
$todaySales = ['count' => 0, 'total' => 0];

// Get summary data
try {
    $db = Database::getInstance()->getConnection();
    
    // Get product count
    $stmt = $db->query("SELECT COUNT(*) FROM products WHERE status = 'active'");
    $activeProducts = (int)$stmt->fetchColumn();
    
    // Get low stock count
    $stmt = $db->query("SELECT COUNT(*) FROM products WHERE stock_level <= min_stock_level AND status = 'active'");
    $lowStockCount = (int)$stmt->fetchColumn();
    
    // Get customer count
    $stmt = $db->query("SELECT COUNT(*) FROM customers");
    $customerCount = (int)$stmt->fetchColumn();
    
    // Get all time sales
    $stmt = $db->query("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM sales");
    $salesData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get today's sales
    $today = date('Y-m-d');
    $stmt = $db->prepare("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total 
                         FROM sales 
                         WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $todaySales = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
}
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
        <p>Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</p>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="fas fa-box"></i> Active Products
                </h5>
                <p class="card-text h2"><?php echo number_format($activeProducts); ?></p>
                <?php if($lowStockCount > 0): ?>
                    <small class="text-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <?php echo $lowStockCount; ?> items low on stock
                    </small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="fas fa-users"></i> Customers
                </h5>
                <p class="card-text h2"><?php echo number_format($customerCount); ?></p>
                <small>Total registered customers</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="fas fa-shopping-cart"></i> Total Sales
                </h5>
                <p class="card-text h2"><?php echo number_format($salesData['count']); ?></p>
                <small>$<?php echo number_format($salesData['total'], 2); ?> total revenue</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="fas fa-clock"></i> Today's Sales
                </h5>
                <p class="card-text h2"><?php echo number_format($todaySales['count']); ?></p>
                <small>$<?php echo number_format($todaySales['total'], 2); ?> today</small>
            </div>
        </div>
    </div>
</div>

<!-- Main Navigation Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="fas fa-tags"></i> Categories
                </h5>
                <p class="card-text">Manage product categories</p>
                <a href="<?php echo SITE_URL; ?>/modules/categories/" class="btn btn-primary">
                    <i class="fas fa-arrow-right"></i> Go to Categories
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="fas fa-box"></i> Products
                </h5>
                <p class="card-text">Manage your product inventory</p>
                <a href="<?php echo SITE_URL; ?>/modules/products/" class="btn btn-primary">
                    <i class="fas fa-arrow-right"></i> Go to Products
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="fas fa-users"></i> Customers
                </h5>
                <p class="card-text">Manage customer information</p>
                <a href="<?php echo SITE_URL; ?>/modules/customers/" class="btn btn-primary">
                    <i class="fas fa-arrow-right"></i> Go to Customers
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="fas fa-shopping-cart"></i> Sales
                </h5>
                <p class="card-text">Process sales and view history</p>
                <a href="<?php echo SITE_URL; ?>/modules/sales/" class="btn btn-primary">
                    <i class="fas fa-arrow-right"></i> Go to Sales
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Additional Information -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-chart-line"></i> Recent Sales</h5>
            </div>
            <div class="card-body">
                <?php if($salesData['count'] > 0): ?>
                    <p>Recent sales will be displayed here</p>
                <?php else: ?>
                    <p class="text-muted">No sales records found</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-exclamation-triangle"></i> Low Stock Alerts</h5>
            </div>
            <div class="card-body">
                <?php if($lowStockCount > 0): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo $lowStockCount; ?> products are at or below minimum stock level.
                    </div>
                <?php else: ?>
                    <p class="text-success">
                        <i class="fas fa-check-circle"></i> All products are above minimum stock levels
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
