<?php
session_start();
require_once '../../config/database.php';
require_once '../../classes/Supplier.php';
require_once '../../includes/functions.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Initialize Supplier class
$supplier = new Supplier();

// Get all suppliers
$suppliers = $supplier->getAll();

// Handle status messages
$statusMessage = '';
if(isset($_SESSION['status_message'])) {
    $statusMessage = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

// Set up the page variables for the template
$page_title = "Suppliers";
$page_icon = "fas fa-truck";
$add_new_text = "Add New Supplier";
$id_field = 'supplier_id'; // Add this line to specify which ID field to use
$table_headers = [
    'supplier_id' => 'ID',
    'company_name' => 'Company Name',
    'contact_name' => 'Contact Person',
    'email' => 'Email',
    'phone' => 'Phone',
    'abn' => 'ABN',
    'status' => 'Status'
];


// Format the data for the template
$items = array_map(function($supplier) {
    $supplier['status'] = '<span class="badge bg-' . ($supplier['status'] == 'active' ? 'success' : 'danger') . '">' 
                         . ucfirst($supplier['status']) . '</span>';
    return $supplier;
}, $suppliers);

// Include header
require_once '../../includes/header.php';
?>

<div class="container mt-4">
    <?php if($statusMessage): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $statusMessage; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php
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
                Are you sure you want to delete this supplier?
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
