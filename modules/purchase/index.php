<?php
session_start();
require_once '../../config/database.php';
require_once '../../classes/Purchase.php';
require_once '../../includes/functions.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Initialize Purchase class
$purchase = new Purchase();

// Get all purchases
$purchases = $purchase->getAll();

// Handle status messages
$statusMessage = '';
if(isset($_SESSION['status_message'])) {
    $statusMessage = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

// Set up the page variables for the template
$page_title = "Purchases";
$page_icon = "fas fa-shopping-basket";
$add_new_text = "New Purchase Order";
$id_field = 'purchase_id'; // Add this line to specify which ID field to use
$table_headers = [
    'purchase_date' => 'Date',
    'reference_no' => 'Reference No',
    'supplier_name' => 'Supplier',
    'total_amount' => 'Total Amount',
    'status' => 'Status',
    'created_by' => 'Created By'
];



// Format the data for the template
$items = array_map(function($p) {
    // Format date
    $p['purchase_date'] = date('d/m/Y', strtotime($p['purchase_date']));
    
    // Format amount
    $p['total_amount'] = '$' . number_format($p['total_amount'], 2);
    
    // Format status badge
    $statusColor = $p['status'] == 'received' ? 'success' : 
                  ($p['status'] == 'pending' ? 'warning' : 'danger');
    $p['status'] = '<span class="badge bg-' . $statusColor . '">' 
                   . ucfirst($p['status']) . '</span>';
    
    // Format created by
    $p['created_by'] = htmlspecialchars($p['first_name'] . ' ' . $p['last_name']);
    
    return $p;
}, $purchases);

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
                Are you sure you want to delete this purchase order?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
// Custom action buttons based on status
function getActionButtons(item) {
    let buttons = `
        <a href="view.php?id=${item.purchase_id}" 
           class="btn btn-sm btn-info text-white" 
           title="View">
            <i class="fas fa-eye"></i>
        </a>`;
    
    if (item.status.includes('pending')) {
        buttons += `
            <a href="edit.php?id=${item.purchase_id}" 
               class="btn btn-sm btn-primary" 
               title="Edit">
                <i class="fas fa-edit"></i>
            </a>
            <button type="button" 
                    class="btn btn-sm btn-danger" 
                    title="Delete"
                    onclick="deleteItem(${item.purchase_id})">
                <i class="fas fa-trash"></i>
            </button>`;
    }
    
    return buttons;
}

function deleteItem(id) {
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
    
    document.getElementById('confirmDelete').onclick = function() {
        window.location.href = `delete.php?id=${id}`;
    };
}
</script>

<?php require_once '../../includes/footer.php'; ?>
