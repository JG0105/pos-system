<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/database.php';
require_once '../../classes/Product.php';
require_once '../../includes/functions.php';

// Initialize Product class
$product = new Product();
$products = $product->getAll();

// Calculate statistics
$total_products = count($products);
$active_products = array_filter($products, function($item) {
    return $item['status'] == 'active';
});
$total_active = count($active_products);

$low_stock = array_filter($products, function($item) {
    return $item['stock_level'] <= $item['min_stock_level'];
});
$low_stock_count = count($low_stock);

$total_value = array_reduce($products, function($carry, $item) {
    return $carry + ($item['stock_level'] * $item['unit_price']);
}, 0);

// Set up the page variables for the template
$page_title = "Products";
$page_icon = "fas fa-box";
$add_new_text = "Add New Product";
$id_field = 'product_id';

// Set up header stats
$show_header_stats = true;
$header_stats = [
    [
        'title' => 'Total Products',
        'value' => $total_products,
        'subtitle' => 'All Products',
        'color' => 'primary'
    ],
    [
        'title' => 'Active Products',
        'value' => $total_active,
        'subtitle' => 'Currently Active',
        'color' => 'success'
    ],
    [
        'title' => 'Low Stock',
        'value' => $low_stock_count,
        'subtitle' => 'Need Attention',
        'color' => 'warning'
    ],
    [
        'title' => 'Stock Value',
        'value' => '$' . number_format($total_value, 2),
        'subtitle' => 'Total Inventory',
        'color' => 'info'
    ]
];

$table_headers = [
    'sku' => 'SKU',
    'product_name' => 'Name',
    'unit_price' => 'Price',
    'stock_level' => 'Stock',
    'min_stock_level' => 'Min Stock',
    'unit_of_measure' => 'Unit',
    'status' => 'Status'
];

// Format the data for the template
$items = array_map(function($item) {
    $stockClass = $item['stock_level'] <= $item['min_stock_level'] ? 'text-danger fw-bold' : '';
    
    return [
        'product_id' => $item['product_id'],
        'sku' => htmlspecialchars($item['sku']),
        'product_name' => htmlspecialchars($item['product_name']),
        'unit_price' => '$' . number_format($item['unit_price'], 2),
        'stock_level' => '<span class="' . $stockClass . '">' . $item['stock_level'] . '</span>',
        'min_stock_level' => $item['min_stock_level'],
        'unit_of_measure' => htmlspecialchars($item['unit_of_measure']),
        'status' => '<span class="badge bg-' . ($item['status'] == 'active' ? 'success' : 'danger') . '">' 
                    . ucfirst($item['status']) . '</span>'
    ];
}, $products);

// Handle status messages
if(isset($_GET['success'])) {
    $statusMessage = '';
    switch($_GET['success']) {
        case '1':
            $statusMessage = "Product created successfully!";
            break;
        case '2':
            $statusMessage = "Product updated successfully!";
            break;
        case '3':
            $statusMessage = "Product \"" . htmlspecialchars($_GET['deleted']) . "\" deleted successfully!";
            break;
    }
}

if(isset($_GET['error'])) {
    $statusMessage = $_GET['error'];
}

// Include header
require_once '../../includes/header.php';
?>

<div class="container mt-4">
    <?php if(isset($statusMessage)): ?>
        <div class="alert alert-<?php echo isset($_GET['success']) ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
            <?php echo $statusMessage; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php
    // Include the template
    require_once ROOT_PATH . '/templates/table_layout_1.php';
    ?>

    <?php if($low_stock_count > 0): ?>
        <div class="card mt-3">
            <div class="card-body">
                <h5 class="card-title">Stock Summary</h5>
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Low Stock Alert:</strong> <?php echo $low_stock_count; ?> products are at or below minimum stock level.
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this product?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
function deleteItem(id) {
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
    
    document.getElementById('confirmDelete').onclick = function() {
        window.location.href = `delete.php?id=${id}`;
    };
}
</script>

<?php require_once '../../includes/footer.php'; ?>
