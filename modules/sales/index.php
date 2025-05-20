<?php
session_start();
require_once '../../config/database.php';
require_once '../../classes/Sales.php';
require_once '../../includes/functions.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Initialize Sales class
$sales = new Sales();

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Get sales data based on filters
$sales_data = $date_from && $date_to ? 
    $sales->getSalesByDateRange($date_from, $date_to) : 
    $sales->getAll($status);

// Get summary data
$period_totals = $sales->getTotalSales($date_from, $date_to);
$today_totals = $sales->getTodaySales();

// Handle status messages
$statusMessage = '';
if(isset($_SESSION['status_message'])) {
    $statusMessage = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

// Set up the page variables for the template
$page_title = "Sales";
$page_icon = "fas fa-shopping-cart";
$add_new_text = "New Sale";
$id_field = 'sale_id';
$table_headers = [
    'sale_date' => 'Date',
    'invoice_number' => 'Invoice #',
    'customer_name' => 'Customer',
    'items_count' => 'Items',
    'subtotal' => 'Subtotal',
    'tax_amount' => 'GST',
    'total_amount' => 'Total',
    'payment_method' => 'Payment',
    'payment_status' => 'Status'
];

// Format the data for the template
$items = array_map(function($sale) use ($sales) {
    $formatted_sale = [
        'sale_id' => $sale['sale_id'],
        'sale_date' => date('d/m/Y', strtotime($sale['sale_date'])),
        'invoice_number' => 'INV-' . str_pad($sale['sale_id'], 6, '0', STR_PAD_LEFT),
        'customer_name' => $sale['company_name'] ? 
            htmlspecialchars($sale['company_name']) : 
            htmlspecialchars($sale['first_name'] . ' ' . $sale['last_name']),
        'items_count' => $sales->getItemCount($sale['sale_id']),
        'subtotal' => '$' . number_format($sale['subtotal'], 2),
        'tax_amount' => '$' . number_format($sale['tax_amount'], 2),
        'total_amount' => '$' . number_format($sale['total_amount'], 2),
        'payment_method' => ucfirst(str_replace('_', ' ', $sale['payment_method'])),
        'payment_status' => '<span class="badge bg-' . 
            ($sale['payment_status'] == 'paid' ? 'success' : 
            ($sale['payment_status'] == 'pending' ? 'warning' : 'danger')) . 
            '">' . ucfirst($sale['payment_status']) . '</span>',
        'status_raw' => $sale['payment_status'] // Used for action buttons logic
    ];
    return $formatted_sale;
}, $sales_data);

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

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Period Total</h6>
                    <h3 class="card-text">$<?php echo number_format($period_totals['total'], 2); ?></h3>
                    <small><?php echo $period_totals['count']; ?> sales</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Today's Sales</h6>
                    <h3 class="card-text">$<?php echo number_format($today_totals['total'], 2); ?></h3>
                    <small><?php echo $today_totals['count']; ?> sales today</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">Period GST</h6>
                    <h3 class="card-text">$<?php echo number_format($period_totals['tax_total'], 2); ?></h3>
                    <small>Total GST collected</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Payment Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="paid" <?php echo $status == 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?php echo $date_from; ?>">
                </div>
                <div class="col-md-3">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="<?php echo $date_to; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label"> </label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

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
                Are you sure you want to delete this sale? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
function getActionButtons(item) {
    let buttons = `
        <a href="view.php?id=${item.sale_id}" class="btn btn-sm btn-info text-white" title="View">
            <i class="fas fa-eye"></i>
        </a>`;
    
    if (item.status_raw === 'pending') {
        buttons += `
            <a href="edit.php?id=${item.sale_id}" class="btn btn-sm btn-primary" title="Edit">
                <i class="fas fa-edit"></i>
            </a>
            <button type="button" class="btn btn-sm btn-success" onclick="markAsPaid(${item.sale_id})" title="Mark as Paid">
                <i class="fas fa-check"></i>
            </button>
            <button type="button" class="btn btn-sm btn-danger" onclick="deleteItem(${item.sale_id})" title="Delete">
                <i class="fas fa-trash"></i>
            </button>`;
    }
    
    buttons += `
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-print"></i>
            </button>
            <ul class="dropdown-menu">
                <li>
                    <a class="dropdown-item" href="#" onclick="openPrintWindow('invoice', ${item.sale_id}); return false;">
                        <i class="fas fa-file-invoice"></i> Tax Invoice
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="#" onclick="openPrintWindow('receipt', ${item.sale_id}); return false;">
                        <i class="fas fa-receipt"></i> Receipt
                    </a>
                </li>
            </ul>
        </div>`;
    
    return buttons;
}

function markAsPaid(id) {
    if(confirm('Mark this sale as paid?')) {
        window.location.href = 'mark_paid.php?id=' + id;
    }
}

function deleteItem(id) {
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
    
    document.getElementById('confirmDelete').onclick = function() {
        window.location.href = `delete.php?id=${id}`;
    };
}

function openPrintWindow(type, id) {
    var width = type === 'receipt' ? 800 : 1024;
    var height = 800;
    var left = (screen.width - width) / 2;
    var top = (screen.height - height) / 2;
    
    window.open(
        'print.php?id=' + id + '&type=' + type,
        'print_window',
        'width=' + width + ',height=' + height + ',top=' + top + ',left=' + left
    );
}
</script>

<?php require_once '../../includes/footer.php'; ?>
