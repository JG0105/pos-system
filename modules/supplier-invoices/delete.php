<?php
session_start();
require_once '../../config/database.php';
require_once '../../classes/SupplierInvoice.php';
require_once '../../includes/functions.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Check if ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['status_message'] = 'Invalid invoice ID';
    header("Location: index.php");
    exit();
}

$invoice_id = (int)$_GET['id'];
$supplierInvoice = new SupplierInvoice();

// Get invoice data
$invoice_data = $supplierInvoice->getById($invoice_id);
if(!$invoice_data || $invoice_data['payment_status'] != 'unpaid') {
    $_SESSION['status_message'] = 'Invoice not found or cannot be deleted';
    header("Location: index.php");
    exit();
}

// Check if there are any payments
if(!empty($invoice_data['payments'])) {
    $_SESSION['status_message'] = 'Cannot delete invoice with recorded payments';
    header("Location: index.php");
    exit();
}

// Attempt to delete the invoice
if($supplierInvoice->delete($invoice_id)) {
    $_SESSION['status_message'] = 'Invoice deleted successfully';
} else {
    $_SESSION['status_message'] = 'Failed to delete invoice';
}

header("Location: index.php");
exit();
?>
