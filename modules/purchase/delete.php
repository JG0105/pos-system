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

// Check if ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['status_message'] = 'Invalid purchase ID';
    header("Location: index.php");
    exit();
}

$purchase_id = (int)$_GET['id'];
$purchase = new Purchase();

// Get purchase data
$purchase_data = $purchase->getById($purchase_id);
if(!$purchase_data || $purchase_data['status'] != 'pending') {
    $_SESSION['status_message'] = 'Purchase order not found or cannot be deleted';
    header("Location: index.php");
    exit();
}

// Attempt to delete the purchase
if($purchase->delete($purchase_id)) {
    $_SESSION['status_message'] = 'Purchase order deleted successfully';
} else {
    $_SESSION['status_message'] = 'Failed to delete purchase order';
}

header("Location: index.php");
exit();
?>
