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
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$stock_level = isset($_GET['stock_level']) ? $_GET['stock_level'] : 'all'; // all, in_stock, low_stock, out_of_stock
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'name'; // name, stock, value

// Get categories for filter
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT category_id, category_name FROM categories ORDER BY category_name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get report data
$stock_data = $reports->getCurrentStockReport($category_id, $stock_level, $sort_by);

// Include header
require_once '../../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-boxes"></i> Current Stock Levels</h1>
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
                <div class="col-md-4">
                    <label for="category_id" class="form-label">Category</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="0">All Categories</option>
                        <?php foreach($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>" 
                                    <?php echo $category_id == $category['category_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="stock_level" class="form-label">Stock Level</label>
                    <select class="form-select" id="stock_level" name="stock_level">
                        <option value="all" <?php echo $stock_level == 'all' ? 'selected' : ''; ?>>All Stock</option>
                        <option value="in_stock" <?php echo $stock_level == 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
                        <option value="low_stock" <?php echo $stock_level == 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                        <option value="out_of_stock" <?php echo $stock_level == 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="sort_by" class="form-label">Sort By</label>
                    <select class="form-select" id="sort_by" name="sort_by">
                        <option value="name" <?php echo $sort_by == 'name' ? 'selected' : ''; ?>>Product Name</option>
                        <option value="stock" <?php echo $sort_by == 'stock' ? 'selected' : ''; ?>>Stock Level</option>
                        <option value="value" <?php echo $sort_by == 'value' ? 'selected' : ''; ?>>Stock Value</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="current_stock.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
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
                    <h5 class="card-title">Total Products</h5>
                    <h3><?php echo number_format($stock_data['total_products']); ?></h3>
                    <small>In Inventory</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Stock Value</h5>
                    <h3>$<?php echo number_format($stock_data['total_value'], 2); ?></h3>
                    <small>At Cost</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Low Stock Items</h5>
                    <h3><?php echo number_format($stock_data['low_stock_count']); ?></h3>
                    <small>Below Minimum Level</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">Out of Stock</h5>
                    <h3><?php echo number_format($stock_data['out_of_stock_count']); ?></h3>
                    <small>Need Reordering</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Level Chart -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Stock Levels by Category</h5>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Stock Status Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Current Stock Details</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th class="text-end">Stock Level</th>
                            <th class="text-end">Min Level</th>
                            <th class="text-end">Reorder Qty</th>
                            <th class="text-end">Unit Cost</th>
                            <th class="text-end">Total Value</th>
                            <th>Status</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($stock_data['products'] as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td class="text-end"><?php echo number_format($product['stock_level']); ?></td>
                                <td class="text-end"><?php echo number_format($product['min_level']); ?></td>
                                <td class="text-end"><?php echo number_format($product['reorder_qty']); ?></td>
                                <td class="text-end">$<?php echo number_format($product['unit_cost'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($product['total_value'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $product['stock_level'] == 0 ? 'danger' : 
                                            ($product['stock_level'] <= $product['min_level'] ? 'warning' : 'success'); 
                                    ?>">
                                        <?php 
                                        echo $product['stock_level'] == 0 ? 'Out of Stock' : 
                                            ($product['stock_level'] <= $product['min_level'] ? 'Low Stock' : 'In Stock'); 
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($product['last_updated'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="../../../modules/products/view.php?id=<?php echo $product['product_id']; ?>" 
                                           class="btn btn-sm btn-info text-white" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="../../../modules/products/edit.php?id=<?php echo $product['product_id']; ?>" 
                                           class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if($product['stock_level'] <= $product['min_level']): ?>
                                            <a href="../../../modules/purchase/add.php?product_id=<?php echo $product['product_id']; ?>" 
                                               class="btn btn-sm btn-success" title="Reorder">
                                                <i class="fas fa-shopping-cart"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Create category chart
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
new Chart(categoryCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($stock_data['category_labels']); ?>,
        datasets: [{
            label: 'Stock Value',
            data: <?php echo json_encode($stock_data['category_values']); ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.5)',
            borderColor: 'rgba(54, 162, 235, 1)',
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

// Create status chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'pie',
    data: {
        labels: ['In Stock', 'Low Stock', 'Out of Stock'],
        datasets: [{
            data: [
                <?php echo $stock_data['in_stock_count']; ?>,
                <?php echo $stock_data['low_stock_count']; ?>,
                <?php echo $stock_data['out_of_stock_count']; ?>
            ],
            backgroundColor: [
                'rgba(40, 167, 69, 0.8)',
                'rgba(255, 193, 7, 0.8)',
                'rgba(220, 53, 69, 0.8)'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': ' + context.raw + ' products';
                    }
                }
            }
        }
    }
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
