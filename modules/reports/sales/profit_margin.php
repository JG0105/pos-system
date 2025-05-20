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
$min_margin = isset($_GET['min_margin']) ? (float)$_GET['min_margin'] : 0;
$max_margin = isset($_GET['max_margin']) ? (float)$_GET['max_margin'] : 100;

// Get categories for filter
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT category_id, category_name FROM categories ORDER BY category_name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get report data
$profit_data = $reports->getProfitMarginReport($start_date, $end_date, $category_id, $min_margin, $max_margin);

// Include header
require_once '../../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-chart-line"></i> Profit Margin Analysis</h1>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-primary" onclick="window.print();">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="profit_margin_pdf.php?<?php echo http_build_query($_GET); ?>" 
                   class="btn btn-danger" target="_blank">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
                <a href="profit_margin_excel.php?<?php echo http_build_query($_GET); ?>" 
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
                    <label for="min_margin" class="form-label">Min Margin %</label>
                    <input type="number" class="form-control" id="min_margin" name="min_margin" 
                           value="<?php echo $min_margin; ?>" min="0" max="100" step="0.1">
                </div>
                <div class="col-md-2">
                    <label for="max_margin" class="form-label">Max Margin %</label>
                    <input type="number" class="form-control" id="max_margin" name="max_margin" 
                           value="<?php echo $max_margin; ?>" min="0" max="100" step="0.1">
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
                    <h3>$<?php echo number_format($profit_data['total_revenue'], 2); ?></h3>
                    <small><?php echo number_format($profit_data['total_transactions']); ?> Sales</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Profit</h5>
                    <h3>$<?php echo number_format($profit_data['total_profit'], 2); ?></h3>
                    <small><?php echo number_format($profit_data['average_margin'], 1); ?>% Avg Margin</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Highest Margin</h5>
                    <h3><?php echo number_format($profit_data['highest_margin']['margin'], 1); ?>%</h3>
                    <small><?php echo htmlspecialchars($profit_data['highest_margin']['product']); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Most Profitable</h5>
                    <h3>$<?php echo number_format($profit_data['most_profitable']['profit'], 2); ?></h3>
                    <small><?php echo htmlspecialchars($profit_data['most_profitable']['product']); ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Margin Distribution Chart -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Margin Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="marginChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Category Margins</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th class="text-end">Revenue</th>
                                    <th class="text-end">Cost</th>
                                    <th class="text-end">Profit</th>
                                    <th class="text-end">Margin %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($profit_data['category_margins'] as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                        <td class="text-end">$<?php echo number_format($category['revenue'], 2); ?></td>
                                        <td class="text-end">$<?php echo number_format($category['cost'], 2); ?></td>
                                        <td class="text-end">$<?php echo number_format($category['profit'], 2); ?></td>
                                        <td class="text-end"><?php echo number_format($category['margin'], 1); ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Margins Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Product Margin Details</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th class="text-end">Quantity</th>
                            <th class="text-end">Revenue</th>
                            <th class="text-end">Cost</th>
                            <th class="text-end">Profit</th>
                            <th class="text-end">Margin %</th>
                            <th class="text-end">Avg Price</th>
                            <th class="text-end">Avg Cost</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($profit_data['product_margins'] as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td class="text-end"><?php echo number_format($product['quantity']); ?></td>
                                <td class="text-end">$<?php echo number_format($product['revenue'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($product['cost'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($product['profit'], 2); ?></td>
                                <td class="text-end"><?php echo number_format($product['margin'], 1); ?>%</td>
                                <td class="text-end">$<?php echo number_format($product['avg_price'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($product['avg_cost'], 2); ?></td>
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

// Create margin distribution chart
const ctx = document.getElementById('marginChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['0-10%', '11-20%', '21-30%', '31-40%', '41-50%', '51%+'],
        datasets: [{
            label: 'Products',
            data: <?php echo json_encode($profit_data['margin_distribution']); ?>,
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
                title: {
                    display: true,
                    text: 'Number of Products'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Profit Margin Range'
                }
            }
        }
    }
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
