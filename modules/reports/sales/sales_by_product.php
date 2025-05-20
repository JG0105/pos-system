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
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'quantity'; // quantity, revenue, profit

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
$product_sales = $reports->getProductSalesReport($start_date, $end_date, $category_id, $product_id, $sort_by);

// Include header
require_once '../../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-box"></i> Sales by Product Report</h1>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-primary" onclick="window.print();">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="sales_by_product_pdf.php?<?php echo http_build_query($_GET); ?>" 
                   class="btn btn-danger" target="_blank">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
                <a href="sales_by_product_excel.php?<?php echo http_build_query($_GET); ?>" 
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
                        <option value="quantity" <?php echo $sort_by == 'quantity' ? 'selected' : ''; ?>>Quantity Sold</option>
                        <option value="revenue" <?php echo $sort_by == 'revenue' ? 'selected' : ''; ?>>Revenue</option>
                        <option value="profit" <?php echo $sort_by == 'profit' ? 'selected' : ''; ?>>Profit</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"> </label>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="sales_by_product.php" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </a>
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
                    <h3>$<?php echo number_format($product_sales['total_revenue'], 2); ?></h3>
                    <small><?php echo $product_sales['total_products']; ?> Products Sold</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Profit</h5>
                    <h3>$<?php echo number_format($product_sales['total_profit'], 2); ?></h3>
                    <small><?php echo number_format($product_sales['profit_margin'], 1); ?>% Margin</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Quantity</h5>
                    <h3><?php echo number_format($product_sales['total_quantity']); ?></h3>
                    <small>Units Sold</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Average Sale</h5>
                    <h3>$<?php echo number_format($product_sales['average_sale'], 2); ?></h3>
                    <small>Per Unit</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Product Sales Details</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th class="text-end">Quantity</th>
                            <th class="text-end">Revenue</th>
                            <th class="text-end">Cost</th>
                            <th class="text-end">Profit</th>
                            <th class="text-end">Margin</th>
                            <th class="text-end">GST</th>
                            <th class="text-end">Last Sale</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($product_sales['products'] as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td class="text-end"><?php echo number_format($product['quantity']); ?></td>
                                <td class="text-end">$<?php echo number_format($product['revenue'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($product['cost'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($product['profit'], 2); ?></td>
                                <td class="text-end"><?php echo number_format($product['margin'], 1); ?>%</td>
                                <td class="text-end">$<?php echo number_format($product['gst'], 2); ?></td>
                                <td class="text-end"><?php echo date('d/m/Y', strtotime($product['last_sale'])); ?></td>
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
                    <tfoot>
                        <tr class="table-primary">
                            <th colspan="2">Total</th>
                            <th class="text-end"><?php echo number_format($product_sales['total_quantity']); ?></th>
                            <th class="text-end">$<?php echo number_format($product_sales['total_revenue'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($product_sales['total_cost'], 2); ?></th>
                            <th class="text-end">$<?php echo number_format($product_sales['total_profit'], 2); ?></th>
                            <th class="text-end"><?php echo number_format($product_sales['profit_margin'], 1); ?>%</th>
                            <th class="text-end">$<?php echo number_format($product_sales['total_gst'], 2); ?></th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

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
</script>

<?php require_once '../../../includes/footer.php'; ?>
