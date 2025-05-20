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
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20; // Default to top 20
$metric = isset($_GET['metric']) ? $_GET['metric'] : 'quantity'; // quantity, revenue, profit
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

// Get categories for filter
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT category_id, category_name FROM categories ORDER BY category_name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get report data
$top_products = $reports->getTopSellingProducts($start_date, $end_date, $limit, $metric, $category_id);

// Include header
require_once '../../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-trophy"></i> Top Selling Products</h1>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-primary" onclick="window.print();">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="top_selling_pdf.php?<?php echo http_build_query($_GET); ?>" 
                   class="btn btn-danger" target="_blank">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
                <a href="top_selling_excel.php?<?php echo http_build_query($_GET); ?>" 
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
                <div class="col-md-2">
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
                <div class="col-md-2">
                    <label for="metric" class="form-label">Rank By</label>
                    <select class="form-select" id="metric" name="metric">
                        <option value="quantity" <?php echo $metric == 'quantity' ? 'selected' : ''; ?>>Quantity Sold</option>
                        <option value="revenue" <?php echo $metric == 'revenue' ? 'selected' : ''; ?>>Revenue</option>
                        <option value="profit" <?php echo $metric == 'profit' ? 'selected' : ''; ?>>Profit</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="limit" class="form-label">Show Top</label>
                    <select class="form-select" id="limit" name="limit">
                        <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>Top 10</option>
                        <option value="20" <?php echo $limit == 20 ? 'selected' : ''; ?>>Top 20</option>
                        <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>Top 50</option>
                        <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>Top 100</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"> </label>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Update
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
                    <h5 class="card-title">Total Revenue</h5>
                    <h3>$<?php echo number_format($top_products['total_revenue'], 2); ?></h3>
                    <small><?php echo number_format($top_products['total_quantity']); ?> Units Sold</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Profit</h5>
                    <h3>$<?php echo number_format($top_products['total_profit'], 2); ?></h3>
                    <small><?php echo number_format($top_products['average_margin'], 1); ?>% Avg Margin</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Top Product</h5>
                    <h3><?php echo number_format($top_products['top_product']['quantity']); ?></h3>
                    <small><?php echo htmlspecialchars($top_products['top_product']['name']); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Most Profitable</h5>
                    <h3>$<?php echo number_format($top_products['most_profitable']['profit'], 2); ?></h3>
                    <small><?php echo htmlspecialchars($top_products['most_profitable']['name']); ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Top Selling Products</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th class="text-end">Quantity</th>
                            <th class="text-end">Revenue</th>
                            <th class="text-end">Cost</th>
                            <th class="text-end">Profit</th>
                            <th class="text-end">Margin</th>
                            <th class="text-end">Avg Price</th>
                            <th>Trend</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($top_products['products'] as $rank => $product): ?>
                            <tr>
                                <td><?php echo $rank + 1; ?></td>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td class="text-end"><?php echo number_format($product['quantity']); ?></td>
                                <td class="text-end">$<?php echo number_format($product['revenue'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($product['cost'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($product['profit'], 2); ?></td>
                                <td class="text-end"><?php echo number_format($product['margin'], 1); ?>%</td>
                                <td class="text-end">$<?php echo number_format($product['average_price'], 2); ?></td>
                                <td class="text-center">
                                    <?php if($product['trend'] > 0): ?>
                                        <i class="fas fa-arrow-up text-success"></i>
                                    <?php elseif($product['trend'] < 0): ?>
                                        <i class="fas fa-arrow-down text-danger"></i>
                                    <?php else: ?>
                                        <i class="fas fa-minus text-muted"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="product_detail.php?id=<?php echo $product['product_id']; ?>" 
                                       class="btn btn-sm btn-info text-white" title="View Details">
                                        <i class="fas fa-search"></i>
                                    </a>
                                    <a href="../../../modules/products/edit.php?id=<?php echo $product['product_id']; ?>" 
                                       class="btn btn-sm btn-primary" title="Edit Product">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Performance Chart -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Top 10 Products Performance</h5>
        </div>
        <div class="card-body">
            <canvas id="productChart" height="300"></canvas>
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

// Create performance chart
const ctx = document.getElementById('productChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_slice(array_column($top_products['products'], 'product_name'), 0, 10)); ?>,
        datasets: [{
            label: 'Revenue',
            data: <?php echo json_encode(array_slice(array_column($top_products['products'], 'revenue'), 0, 10)); ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.5)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        },
        {
            label: 'Profit',
            data: <?php echo json_encode(array_slice(array_column($top_products['products'], 'profit'), 0, 10)); ?>,
            backgroundColor: 'rgba(75, 192, 192, 0.5)',
            borderColor: 'rgba(75, 192, 192, 1)',
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
                        return context.dataset.label + ': $' + context.raw.toLocaleString();
                    }
                }
            }
        }
    }
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
