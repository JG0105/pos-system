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
$min_value = isset($_GET['min_value']) ? (float)$_GET['min_value'] : 0;
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'value'; // value, name, stock, cost
$valuation_method = isset($_GET['valuation_method']) ? $_GET['valuation_method'] : 'average'; // average, fifo, last

// Get categories for filter
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT category_id, category_name FROM categories ORDER BY category_name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get report data
$valuation_data = $reports->getStockValuationReport($category_id, $min_value, $sort_by, $valuation_method);

// Include header
require_once '../../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-calculator"></i> Stock Valuation</h1>
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
                <div class="col-md-3">
                    <label for="valuation_method" class="form-label">Valuation Method</label>
                    <select class="form-select" id="valuation_method" name="valuation_method">
                        <option value="average" <?php echo $valuation_method == 'average' ? 'selected' : ''; ?>>Average Cost</option>
                        <option value="fifo" <?php echo $valuation_method == 'fifo' ? 'selected' : ''; ?>>FIFO</option>
                        <option value="last" <?php echo $valuation_method == 'last' ? 'selected' : ''; ?>>Last Purchase Price</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="min_value" class="form-label">Minimum Value</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="min_value" name="min_value" 
                               value="<?php echo $min_value; ?>" min="0" step="0.01">
                    </div>
                </div>
                <div class="col-md-2">
                    <label for="sort_by" class="form-label">Sort By</label>
                    <select class="form-select" id="sort_by" name="sort_by">
                        <option value="value" <?php echo $sort_by == 'value' ? 'selected' : ''; ?>>Total Value</option>
                        <option value="name" <?php echo $sort_by == 'name' ? 'selected' : ''; ?>>Product Name</option>
                        <option value="stock" <?php echo $sort_by == 'stock' ? 'selected' : ''; ?>>Stock Level</option>
                        <option value="cost" <?php echo $sort_by == 'cost' ? 'selected' : ''; ?>>Unit Cost</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"> </label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filter
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
                    <h5 class="card-title">Total Stock Value</h5>
                    <h3>$<?php echo number_format($valuation_data['total_value'], 2); ?></h3>
                    <small><?php echo number_format($valuation_data['total_products']); ?> Products</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Average Value</h5>
                    <h3>$<?php echo number_format($valuation_data['average_value'], 2); ?></h3>
                    <small>Per Product</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Highest Value</h5>
                    <h3>$<?php echo number_format($valuation_data['highest_value'], 2); ?></h3>
                    <small><?php echo htmlspecialchars($valuation_data['highest_product']); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Value Change</h5>
                    <h3><?php echo ($valuation_data['value_change'] >= 0 ? '+' : '') . 
                              number_format($valuation_data['value_change'], 1); ?>%</h3>
                    <small>Last 30 Days</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Value Chart -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Value by Category</h5>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Value Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="distributionChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Valuation Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Stock Valuation Details</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th class="text-end">Stock Level</th>
                            <th class="text-end">Average Cost</th>
                            <th class="text-end">Last Cost</th>
                            <th class="text-end">Total Value</th>
                            <th class="text-end">Retail Value</th>
                            <th class="text-end">Margin</th>
                            <th>Cost Trend</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($valuation_data['products'] as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td class="text-end"><?php echo number_format($product['stock_level']); ?></td>
                                <td class="text-end">$<?php echo number_format($product['average_cost'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($product['last_cost'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($product['total_value'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($product['retail_value'], 2); ?></td>
                                <td class="text-end"><?php echo number_format($product['margin'], 1); ?>%</td>
                                <td class="text-center">
                                    <?php if($product['cost_trend'] > 0): ?>
                                        <i class="fas fa-arrow-up text-danger" title="Cost Increasing"></i>
                                    <?php elseif($product['cost_trend'] < 0): ?>
                                        <i class="fas fa-arrow-down text-success" title="Cost Decreasing"></i>
                                    <?php else: ?>
                                        <i class="fas fa-minus text-muted" title="Cost Stable"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="../../../modules/products/view.php?id=<?php echo $product['product_id']; ?>" 
                                           class="btn btn-sm btn-info text-white" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="../../../modules/products/cost_history.php?id=<?php echo $product['product_id']; ?>" 
                                           class="btn btn-sm btn-primary" title="Cost History">
                                            <i class="fas fa-history"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th colspan="2">Total</th>
                            <th class="text-end"><?php echo number_format($valuation_data['total_stock']); ?></th>
                            <th colspan="2"></th>
                            <th class="text-end">$<?php echo number_format($valuation_data['total_value'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($valuation_data['total_retail'], 2); ?></th>
                            <th class="text-end"><?php echo number_format($valuation_data['average_margin'], 1); ?>%</th>
                            <th colspan="2"></th>
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
// Create category value chart
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
new Chart(categoryCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($valuation_data['category_labels']); ?>,
        datasets: [{
            label: 'Stock Value',
            data: <?php echo json_encode($valuation_data['category_values']); ?>,
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

// Create value distribution chart
const distributionCtx = document.getElementById('distributionChart').getContext('2d');
new Chart(distributionCtx, {
    type: 'pie',
    data: {
        labels: ['High Value', 'Medium Value', 'Low Value'],
        datasets: [{
            data: [
                <?php echo $valuation_data['high_value_count']; ?>,
                <?php echo $valuation_data['medium_value_count']; ?>,
                <?php echo $valuation_data['low_value_count']; ?>
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
