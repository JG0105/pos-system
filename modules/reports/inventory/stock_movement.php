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
$movement_type = isset($_GET['movement_type']) ? $_GET['movement_type'] : 'all'; // all, in, out, adjustment

// Get categories for filter
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT category_id, category_name FROM categories ORDER BY category_name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get products for filter
$products_sql = "SELECT product_id, product_name FROM products";
if($category_id) {
    $products_sql .= " WHERE category_id = " . $category_id;
}
$products_sql .= " ORDER BY product_name";
$stmt = $db->query($products_sql);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get report data
$movement_data = $reports->getStockMovementReport($start_date, $end_date, $category_id, $product_id, $movement_type);

// Include header
require_once '../../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-exchange-alt"></i> Stock Movement History</h1>
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
                    <label for="movement_type" class="form-label">Movement Type</label>
                    <select class="form-select" id="movement_type" name="movement_type">
                        <option value="all" <?php echo $movement_type == 'all' ? 'selected' : ''; ?>>All Movements</option>
                        <option value="in" <?php echo $movement_type == 'in' ? 'selected' : ''; ?>>Stock In</option>
                        <option value="out" <?php echo $movement_type == 'out' ? 'selected' : ''; ?>>Stock Out</option>
                        <option value="adjustment" <?php echo $movement_type == 'adjustment' ? 'selected' : ''; ?>>Adjustments</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="stock_movement.php" class="btn btn-secondary">
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
                    <h5 class="card-title">Total Movements</h5>
                    <h3><?php echo number_format($movement_data['total_movements']); ?></h3>
                    <small>Transactions</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Stock In</h5>
                    <h3><?php echo number_format($movement_data['total_in']); ?></h3>
                    <small>Units Received</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">Stock Out</h5>
                    <h3><?php echo number_format($movement_data['total_out']); ?></h3>
                    <small>Units Sold/Used</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Net Change</h5>
                    <h3><?php echo number_format($movement_data['net_change']); ?></h3>
                    <small>Unit Difference</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Movement Charts -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Daily Movement Trend</h5>
                </div>
                <div class="card-body">
                    <canvas id="trendChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Movement Type Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="typeChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Movement Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Stock Movement Details</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Reference</th>
                            <th class="text-end">Quantity</th>
                            <th class="text-end">Previous Stock</th>
                            <th class="text-end">New Stock</th>
                            <th>Source</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($movement_data['movements'] as $movement): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($movement['movement_date'])); ?></td>
                                <td><?php echo htmlspecialchars($movement['product_name']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $movement['movement_type'] == 'in' ? 'success' : 
                                            ($movement['movement_type'] == 'out' ? 'danger' : 'warning'); 
                                    ?>">
                                        <?php echo ucfirst($movement['movement_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($movement['reference_no']); ?></td>
                                <td class="text-end">
                                    <?php 
                                    $prefix = $movement['movement_type'] == 'in' ? '+' : 
                                        ($movement['movement_type'] == 'out' ? '-' : '');
                                    echo $prefix . number_format(abs($movement['quantity'])); 
                                    ?>
                                </td>
                                <td class="text-end"><?php echo number_format($movement['previous_stock']); ?></td>
                                <td class="text-end"><?php echo number_format($movement['new_stock']); ?></td>
                                <td><?php echo htmlspecialchars($movement['source']); ?></td>
                                <td><?php echo htmlspecialchars($movement['notes']); ?></td>
                                <td>
                                    <?php if($movement['source_type'] == 'purchase'): ?>
                                        <a href="../../../modules/purchase/view.php?id=<?php echo $movement['source_id']; ?>" 
                                           class="btn btn-sm btn-info text-white" title="View Purchase">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    <?php elseif($movement['source_type'] == 'sale'): ?>
                                        <a href="../../../modules/sales/view.php?id=<?php echo $movement['source_id']; ?>" 
                                           class="btn btn-sm btn-info text-white" title="View Sale">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    <?php endif; ?>
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

// Create trend chart
const trendCtx = document.getElementById('trendChart').getContext('2d');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($movement_data['trend_labels']); ?>,
        datasets: [{
            label: 'Stock In',
            data: <?php echo json_encode($movement_data['trend_in']); ?>,
            borderColor: 'rgb(40, 167, 69)',
            tension: 0.1
        },
        {
            label: 'Stock Out',
            data: <?php echo json_encode($movement_data['trend_out']); ?>,
            borderColor: 'rgb(220, 53, 69)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Create type distribution chart
const typeCtx = document.getElementById('typeChart').getContext('2d');
new Chart(typeCtx, {
    type: 'pie',
    data: {
        labels: ['Stock In', 'Stock Out', 'Adjustments'],
        datasets: [{
            data: [
                <?php echo $movement_data['total_in']; ?>,
                <?php echo $movement_data['total_out']; ?>,
                <?php echo $movement_data['total_adjustments']; ?>
            ],
            backgroundColor: [
                'rgba(40, 167, 69, 0.8)',
                'rgba(220, 53, 69, 0.8)',
                'rgba(255, 193, 7, 0.8)'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': ' + context.raw + ' units';
                    }
                }
            }
        }
    }
});

// Add date validation
document.getElementById('start_date').addEventListener('change', function() {
    document.getElementById('end_date').min = this.value;
});

document.getElementById('end_date').addEventListener('change', function() {
    document.getElementById('start_date').max = this.value;
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
