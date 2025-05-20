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

// Check if ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['status_message'] = 'Invalid sale ID';
    header("Location: index.php");
    exit();
}

$sale_id = (int)$_GET['id'];
$sales = new Sales();

// Get sale data
$sale_data = $sales->getById($sale_id);
if(!$sale_data || $sale_data['payment_status'] != 'pending') {
    $_SESSION['status_message'] = 'Sale not found or cannot be deleted';
    header("Location: index.php");
    exit();
}

// Attempt to delete the sale
if($sales->delete($sale_id)) {
    $_SESSION['status_message'] = 'Sale deleted successfully';
} else {
    $_SESSION['status_message'] = 'Failed to delete sale';
}

header("Location: index.php");
exit();
?>
