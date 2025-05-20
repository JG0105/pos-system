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
$threshold = isset($_GET['threshold']) ? (int)$_GET['threshold'] : 0; // 0 = use product min_level, or specific number
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'urgency'; // urgency, name, stock, days
$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;

// Get categories and suppliers for filters
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT category_id, category_name FROM categories ORDER BY category_name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->query("SELECT supplier_id, company_name FROM suppliers ORDER BY company_name");
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get report data
$low_stock_data = $reports->getLowStockReport($category_id, $threshold, $sort_by, $supplier_id);

// Include header
require_once '../../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-exclamation-triangle"></i> Low Stock Alert</h1>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-primary" onclick="window.print();">
                    <i class="fas fa-print"></i> Print Report
                </button>
                <button type="button" class="btn btn-success" onclick="createPurchaseOrders();">
                    <i class="fas fa-shopping-cart"></i> Create Purchase Orders
                </button>
            </div>
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
                    <label for="supplier_id" class="form-label">Supplier</label>
                    <select class="form-select" id="supplier_id" name="supplier_id">
                        <option value="0">All Suppliers</option>
                        <?php foreach($suppliers as $supplier): ?>
                            <option value="<?php echo $supplier['supplier_id']; ?>" 
                                    <?php echo $supplier_id == $supplier['supplier_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($supplier['company_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="threshold" class="form-label">Threshold Level</label>
                    <input type="number" class="form-control" id="threshold" name="threshold" 
                           value="<?php echo $threshold; ?>" min="0" 
                           placeholder="Use product min level">
                </div>
                <div class="col-md-2">
                    <label for="sort_by" class="form-label">Sort By</label>
                    <select class="form-select" id="sort_by" name="sort_by">
                        <option value="urgency" <?php echo $sort_by == 'urgency' ? 'selected' : ''; ?>>Urgency</option>
                        <option value="name" <?php echo $sort_by == 'name' ? 'selected' : ''; ?>>Product Name</option>
                        <option value="stock" <?php echo $sort_by == 'stock' ? 'selected' : ''; ?>>Stock Level</option>
                        <option value="days" <?php echo $sort_by == 'days' ? 'selected' : ''; ?>>Days Until Empty</option>
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
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">Out of Stock</h5>
                    <h3><?php echo number_format($low_stock_data['out_of_stock']); ?></h3>
                    <small>Products</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Below Minimum</h5>
                    <h3><?php echo number_format($low_stock_data['below_minimum']); ?></h3>
                    <small>Products</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Reorder Value</h5>
                    <h3>$<?php echo number_format($low_stock_data['reorder_value'], 2); ?></h3>
                    <small>Estimated Cost</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Affected Suppliers</h5>
                    <h3><?php echo number_format($low_stock_data['affected_suppliers']); ?></h3>
                    <small>Need to Contact</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Low Stock Items</h5>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="selectAll">
                <label class="form-check-label" for="selectAll">
                    Select All
                </label>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th width="30"></th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Supplier</th>
                            <th class="text-end">Current Stock</th>
                            <th class="text-end">Min Level</th>
                            <th class="text-end">Reorder Qty</th>
                            <th class="text-end">Last Cost</th>
                            <th class="text-end">Reorder Value</th>
                            <th>Days Until Empty</th>
                            <th>Last Ordered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($low_stock_data['products'] as $product): ?>
                            <tr class="<?php echo $product['stock_level'] == 0 ? 'table-danger' : 
                                ($product['days_until_empty'] <= 7 ? 'table-warning' : ''); ?>">
                                <td>
                                    <input type="checkbox" class="form-check-input product-select" 
                                           value="<?php echo $product['product_id']; ?>"
                                           data-supplier="<?php echo $product['supplier_id']; ?>">
                                </td>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['supplier_name']); ?></td>
                                <td class="text-end"><?php echo number_format($product['stock_level']); ?></td>
                                <td class="text-end"><?php echo number_format($product['min_level']); ?></td>
                                <td class="text-end"><?php echo number_format($product['reorder_qty']); ?></td>
                                <td class="text-end">$<?php echo number_format($product['last_cost'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($product['reorder_value'], 2); ?></td>
                                <td>
                                    <?php if($product['days_until_empty'] !== null): ?>
                                        <span class="badge bg-<?php 
                                            echo $product['days_until_empty'] <= 7 ? 'danger' : 
                                                ($product['days_until_empty'] <= 14 ? 'warning' : 'success'); 
                                        ?>">
                                            <?php echo $product['days_until_empty']; ?> days
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $product['last_ordered'] ? date('d/m/Y', strtotime($product['last_ordered'])) : 'Never'; ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="../../../modules/products/view.php?id=<?php echo $product['product_id']; ?>" 
                                           class="btn btn-sm btn-info text-white" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="../../../modules/purchase/add.php?product_id=<?php echo $product['product_id']; ?>" 
                                           class="btn btn-sm btn-success" title="Create PO">
                                            <i class="fas fa-shopping-cart"></i>
                                        </a>
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

<script>
// Select all checkboxes
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.product-select').forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Create purchase orders function
function createPurchaseOrders() {
    const selected = Array.from(document.querySelectorAll('.product-select:checked'));
    
    if(selected.length === 0) {
        alert('Please select at least one product');
        return;
    }

    // Group products by supplier
    const supplierGroups = {};
    selected.forEach(checkbox => {
        const supplierId = checkbox.dataset.supplier;
        if(!supplierGroups[supplierId]) {
            supplierGroups[supplierId] = [];
        }
        supplierGroups[supplierId].push(checkbox.value);
    });

    // Create purchase orders for each supplier
    for(const [supplierId, products] of Object.entries(supplierGroups)) {
        window.open(`../../../modules/purchase/add.php?supplier_id=${supplierId}&products=${products.join(',')}`, '_blank');
    }
}

// Add urgency highlighting
document.querySelectorAll('tr').forEach(row => {
    const daysCell = row.querySelector('td:nth-child(10)');
    if(daysCell) {
        const days = parseInt(daysCell.textContent);
        if(days <= 7) {
            row.classList.add('table-danger');
        } else if(days <= 14) {
            row.classList.add('table-warning');
        }
    }
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
