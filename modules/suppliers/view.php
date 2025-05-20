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

// Check if ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['status_message'] = 'Invalid supplier ID';
    header("Location: index.php");
    exit();
}

$supplier_id = (int)$_GET['id'];
$supplier = new Supplier();

// Get supplier data
$supplier_data = $supplier->getById($supplier_id);
if(!$supplier_data) {
    $_SESSION['status_message'] = 'Supplier not found';
    header("Location: index.php");
    exit();
}

// Get supplier's purchase history
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT p.*, u.first_name, u.last_name 
        FROM purchases p 
        LEFT JOIN users u ON p.user_id = u.user_id 
        WHERE p.supplier_id = ? 
        ORDER BY p.purchase_date DESC
    ");
    $stmt->execute([$supplier_id]);
    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching supplier purchases: " . $e->getMessage());
    $purchases = [];
}

// Include header
require_once '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-truck"></i> View Supplier</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="edit.php?id=<?php echo $supplier_id; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Supplier
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Suppliers
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Supplier Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="30%">Company Name</th>
                            <td><?php echo htmlspecialchars($supplier_data['company_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Contact Person</th>
                            <td><?php echo htmlspecialchars($supplier_data['contact_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>
                                <a href="mailto:<?php echo htmlspecialchars($supplier_data['email']); ?>">
                                    <?php echo htmlspecialchars($supplier_data['email']); ?>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>Phone</th>
                            <td>
                                <a href="tel:<?php echo htmlspecialchars($supplier_data['phone']); ?>">
                                    <?php echo htmlspecialchars($supplier_data['phone']); ?>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>ABN</th>
                            <td><?php echo htmlspecialchars($supplier_data['abn']); ?></td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td><?php echo nl2br(htmlspecialchars($supplier_data['address'])); ?></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <span class="badge bg-<?php echo $supplier_data['status'] == 'active' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst(htmlspecialchars($supplier_data['status'])); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Created At</th>
                            <td><?php echo date('d/m/Y H:i', strtotime($supplier_data['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <th>Last Updated</th>
                            <td><?php echo date('d/m/Y H:i', strtotime($supplier_data['updated_at'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Purchase History</h5>
                </div>
                <div class="card-body">
                    <?php if(!empty($purchases)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Reference</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Created By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($purchases as $purchase): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($purchase['purchase_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($purchase['reference_no']); ?></td>
                                            <td>$<?php echo number_format($purchase['total_amount'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $purchase['status'] == 'received' ? 'success' : 
                                                        ($purchase['status'] == 'pending' ? 'warning' : 'danger'); 
                                                ?>">
                                                    <?php echo ucfirst($purchase['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($purchase['first_name'] . ' ' . $purchase['last_name']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No purchase history found</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
