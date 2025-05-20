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
require_once '../../classes/Customer.php';
require_once '../../includes/functions.php';

// Initialize Customer class
$customer = new Customer();

// Handle search
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$customers = $searchTerm ? $customer->search($searchTerm) : $customer->getAll();

// Calculate statistics
$total_customers = count($customers);
$total_sales = array_sum(array_column($customers, 'sales_count'));
$total_spent = array_sum(array_column($customers, 'total_spent'));
$active_customers = array_filter($customers, function($cust) {
    return strtotime($cust['last_sale_date'] ?? '') >= strtotime('-30 days');
});
$active_count = count($active_customers);

// Set up the page variables for the template
$page_title = "Customers";
$page_icon = "fas fa-users";
$add_new_text = "Add New Customer";
$id_field = 'customer_id';

// Set up header stats
$show_header_stats = true;
$header_stats = [
    [
        'title' => 'Total Customers',
        'value' => $total_customers,
        'subtitle' => 'All Customers',
        'color' => 'primary'
    ],
    [
        'title' => 'Active Customers',
        'value' => $active_count,
        'subtitle' => 'Last 30 Days',
        'color' => 'success'
    ],
    [
        'title' => 'Total Sales',
        'value' => $total_sales,
        'subtitle' => 'All Orders',
        'color' => 'info'
    ],
    [
        'title' => 'Revenue',
        'value' => '$' . number_format($total_spent, 2),
        'subtitle' => 'Total Revenue',
        'color' => 'warning'
    ]
];

$table_headers = [
    'name' => 'Name',
    'company_name' => 'Company',
    'contact' => 'Contact',
    'sales' => 'Sales',
    'total_spent' => 'Total Spent',
    'created_at' => 'Created'
];

// Format the data for the template
$items = array_map(function($cust) {
    $name = htmlspecialchars($cust['first_name'] . ' ' . $cust['last_name']);
    if($cust['abn']) {
        $name .= '<br><small class="text-muted">ABN: ' . htmlspecialchars($cust['abn']) . '</small>';
    }

    $contact = '';
    if($cust['email']) {
        $contact .= '<i class="fas fa-envelope"></i> ' . htmlspecialchars($cust['email']) . '<br>';
    }
    if($cust['phone']) {
        $contact .= '<i class="fas fa-phone"></i> ' . htmlspecialchars($cust['phone']);
    }

    return [
        'customer_id' => $cust['customer_id'],
        'name' => $name,
        'company_name' => htmlspecialchars($cust['company_name'] ?? ''),
        'contact' => $contact,
        'sales' => '<span class="badge bg-info">' . ($cust['sales_count'] ?? 0) . ' orders</span>',
        'total_spent' => '$' . number_format($cust['total_spent'] ?? 0, 2),
        'created_at' => date('d/m/Y', strtotime($cust['created_at'])),
        'sales_count' => $cust['sales_count'] ?? 0 // Used for delete button logic
    ];
}, $customers);

// Handle status messages
if(isset($_GET['success'])) {
    $statusMessage = '';
    switch($_GET['success']) {
        case '1':
            $statusMessage = "Customer added successfully!";
            break;
        case '2':
            $statusMessage = "Customer updated successfully!";
            break;
        case '3':
            $statusMessage = "Customer deleted successfully!";
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

    <!-- Search Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-8">
                    <input type="text" 
                           class="form-control" 
                           name="search" 
                           placeholder="Search customers by name, email, phone or company..."
                           value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <?php if($searchTerm): ?>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <?php
    // Custom action buttons function
    function getActionButtons($item) {
        $buttons = '<div class="btn-group">';
        $buttons .= '<a href="edit.php?id=' . $item['customer_id'] . '" class="btn btn-sm btn-primary" title="Edit">';
        $buttons .= '<i class="fas fa-edit"></i></a>';
        
        if ($item['sales_count'] == 0) {
            $buttons .= '<button type="button" class="btn btn-sm btn-danger" onclick="deleteItem(' . $item['customer_id'] . ')" title="Delete">';
            $buttons .= '<i class="fas fa-trash"></i></button>';
        } else {
            $buttons .= '<button class="btn btn-sm btn-danger" title="Cannot delete: Customer has sales records" disabled>';
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
                Are you sure you want to delete this customer?
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
