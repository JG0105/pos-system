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
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'quantity'; // quantity, amount, latest

// Get categories and products for filters
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT category_id, category_name FROM categories ORDER BY category_name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$products_sql = "SELECT product_id, product_name FROM products";
if($category_id) {
    $products_sql .= " WHERE category_id = " . $category_id;
}
$products_sql .= " ORDER BY product_name";
$stmt = $db->query($products_sql);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get report data
$product_data = $reports->getPurchasesByProductReport($start_date, $end_date, $category_id, $product_id, $sort_by);

// Include header
require_once '../../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-box"></i> Purchases by Product</h1>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-primary" onclick="window.print();">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="purchase_by_product_pdf.php?<?php echo http_build_query($_GET); ?>" 
                   class="btn btn-danger" target="_blank">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
                <a href="purchase_by_product_excel.php?<?php echo http_build_query($_GET); ?>" 
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
                    <label for="product_id" class="form-label">Product</label>
                    <select class="form-select" id="product_id" name="product_id">
                        <option value="0">All Products</option>
                        <?php foreach($products as $product): ?>
                            <option value="<?php echo $product['product_id']; ?>" 
                                    <?php echo $product_id == $product['product_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($product['product_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="sort_by" class="form-label">Sort By</label>
                    <select class="form-select" id="sort_by" name="sort_by">
                        <option value="quantity" <?php echo $sort_by == 'quantity' ? 'selected' : ''; ?>>Quantity</option>
                        <option value="amount" <?php echo $sort_by == 'amount' ? 'selected' : ''; ?>>Amount</option>
                        <option value="latest" <?php echo $sort_by == 'latest' ? 'selected' : ''; ?>>Latest Purchase</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="purchase_by_product.php" class="btn btn-secondary">
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
                    <h5 class="card-title">Total Purchases</h5>
                    <h3>$<?php echo number_format($product_data['total_amount'], 2); ?></h3>
                    <small><?php echo number_format($product_data['total_quantity']); ?> Units</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Products Purchased</h5>
                    <h3><?php echo number_format($product_data['unique_products']); ?></h3>
                    <small>Unique Products</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Average Cost</h5>
                    <h3>$<?php echo number_format($product_data['average_cost'], 2); ?></h3>
                    <small>Per Unit</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Most Purchased</h5>
                    <h3><?php echo number_format($product_data['most_purchased']['quantity']); ?></h3>
                    <small><?php echo htmlspecialchars($product_data['most_purchased']['name']); ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Summary -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Top 10 Products by Purchase Amount</h5>
                </div>
                <div class="card-body">
                    <canvas id="productsChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Category Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Product Purchase Details</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th class="text-end">Quantity</th>
                            <th class="text-end">Total Cost</th>
                            <th class="text-end">Avg Cost</th>
                            <th class="text-end">Last Cost</th>
                            <th class="text-end">GST</th>
                            <th class="text-end">Last Purchase</th>
                            <th>Cost Trend</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($product_data['products'] as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td class="text-end"><?php echo number_format($product['quantity']); ?></td>
                                <td class="text-end">$<?php echo number_format($product['total_cost'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($product['average_cost'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($product['last_cost'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($product['total_gst'], 2); ?></td>
                                <td class="text-end"><?php echo date('d/m/Y', strtotime($product['last_purchase'])); ?></td>
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
                                    <a href="product_purchase_history.php?id=<?php echo $product['product_id']; ?>" 
                                       class="btn btn-sm btn-info text-white" title="View History">
                                        <i class="fas fa-history"></i>
                                    </a>
                                    <a href="../../../modules/products/edit.php?id=<?php echo $product['product_id']; ?>" 
                                       class="btn btn-sm btn-primary" title="Edit Product">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th colspan="2">Total</th>
                            <th class="text-end"><?php echo number_format($product_data['total_quantity']); ?></th>
                            <th class="text-end">$<?php echo number_format($product_data['total_amount'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($product_data['average_cost'], 2); ?></th>
                            <th colspan="5"></th>
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
// Add date validation
document.getElementById('start_date').addEventListener('change', function() {
    document.getElementById('end_date').min = this.value;
});

document.getElementById('end_date').addEventListener('change', function() {
    document.getElementById('start_date').max = this.value;
});

// Update products dropdown when category changes
document.getElementById('category_id').addEventListener('change', function() {
    const categoryId = this.value;
    const productSelect = document.getElementById('product_id');
    
    // Clear current options
    productSelect.innerHTML = '<option value="0">All Products</option>';
    
    if(categoryId != '0') {
        fetch(`get_products.php?category_id=${categoryId}`)
            .then(response => response.json())
            .then(products => {
                products.forEach(product => {
                    const option = document.createElement('option');
                    option.value = product.product_id;
                    option.textContent = product.product_name;
                    productSelect.appendChild(option);
                });
            });
    }
});

// Create products chart
const productsCtx = document.getElementById('productsChart').getContext('2d');
new Chart(productsCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_slice($product_data['chart_labels'], 0, 10)); ?>,
        datasets: [{
            label: 'Purchase Amount',
            data: <?php echo json_encode(array_slice($product_data['chart_values'], 0, 10)); ?>,
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

// Create category distribution chart
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
new Chart(categoryCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($product_data['category_labels']); ?>,
        datasets: [{
            data: <?php echo json_encode($product_data['category_values']); ?>,
            backgroundColor: [
                'rgba(54, 162, 235, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(255, 206, 86, 0.8)',
                'rgba(153, 102, 255, 0.8)',
                'rgba(255, 159, 64, 0.8)'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': ' + context.raw.toFixed(1) + '%';
                    }
                }
            }
        }
    }
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
