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

// Check if supplier exists
$supplier_data = $supplier->getById($supplier_id);
if(!$supplier_data) {
    $_SESSION['status_message'] = 'Supplier not found';
    header("Location: index.php");
    exit();
}

// Check if supplier has associated purchases
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT COUNT(*) FROM purchases WHERE supplier_id = ?");
    $stmt->execute([$supplier_id]);
    $has_purchases = $stmt->fetchColumn() > 0;

    if($has_purchases) {
        $_SESSION['status_message'] = 'Cannot delete supplier: There are purchases associated with this supplier';
        header("Location: index.php");
        exit();
    }
} catch(PDOException $e) {
    error_log("Error checking supplier purchases: " . $e->getMessage());
    $_SESSION['status_message'] = 'Error checking supplier dependencies';
    header("Location: index.php");
    exit();
}

// Attempt to delete the supplier
if($supplier->delete($supplier_id)) {
    $_SESSION['status_message'] = 'Supplier deleted successfully';
} else {
    $_SESSION['status_message'] = 'Failed to delete supplier';
}

header("Location: index.php");
exit();
?>
