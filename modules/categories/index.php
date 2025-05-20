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
require_once '../../classes/Category.php';
require_once '../../includes/functions.php';

// Initialize Category class
$category = new Category();
$categories = $category->getAll();

// Calculate statistics
$total_categories = count($categories);
$total_products = array_sum(array_column($categories, 'product_count'));
$active_categories = $total_categories; // You might want to modify this based on your actual status logic

// Set up the page variables for the template
$page_title = "Categories";
$page_icon = "fas fa-tags";
$add_new_text = "Add New Category";
$id_field = 'category_id';

// Set up header stats
$show_header_stats = true;
$header_stats = [
    [
        'title' => 'Total Categories',
        'value' => $total_categories,
        'subtitle' => 'All Categories',
        'color' => 'primary'
    ],
    [
        'title' => 'Active Categories',
        'value' => $active_categories,
        'subtitle' => 'Currently Active',
        'color' => 'success'
    ],
    [
        'title' => 'Total Products',
        'value' => $total_products,
        'subtitle' => 'Across Categories',
        'color' => 'info'
    ]
];

$table_headers = [
    'category_name' => 'Category Name',
    'description' => 'Description',
    'product_count' => 'Products',
    'created_at' => 'Created'
];

// Format the data for the template
$items = array_map(function($cat) {
    return [
        'category_id' => $cat['category_id'],
        'category_name' => htmlspecialchars($cat['category_name']),
        'description' => htmlspecialchars($cat['description'] ?? ''),
        'product_count' => '<span class="badge bg-info">' . $cat['product_count'] . ' products</span>',
        'created_at' => date('d/m/Y', strtotime($cat['created_at'])),
        'has_products' => $cat['product_count'] > 0 // Used for delete button logic
    ];
}, $categories);

// Handle status messages
if(isset($_GET['success'])) {
    $statusMessage = '';
    switch($_GET['success']) {
        case '1':
            $statusMessage = "Category created successfully!";
            break;
        case '2':
            $statusMessage = "Category updated successfully!";
            break;
        case '3':
            $statusMessage = "Category deleted successfully!";
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
            <?php echo htmlspecialchars($statusMessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php
    // Custom action buttons function
    function getActionButtons($item) {
        $buttons = '<div class="btn-group">';
        $buttons .= '<a href="edit.php?id=' . $item['category_id'] . '" class="btn btn-sm btn-primary" title="Edit">';
        $buttons .= '<i class="fas fa-edit"></i></a>';
        
        if (!$item['has_products']) {
            $buttons .= '<button type="button" class="btn btn-sm btn-danger" onclick="deleteItem(' . $item['category_id'] . ')" title="Delete">';
            $buttons .= '<i class="fas fa-trash"></i></button>';
        } else {
            $buttons .= '<button class="btn btn-sm btn-danger" title="Cannot delete: Category has products" disabled>';
            $buttons .= '<i class="fas fa-trash"></i></button>';
        }
        
        $buttons .= '</div>';
        return $buttons;
    }

    // Include the template
    require_once ROOT_PATH . '/templates/table_layout_1.php';
    ?>
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
                Are you sure you want to delete this category?
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
