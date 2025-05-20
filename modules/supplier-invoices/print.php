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
    die('Invalid invoice ID');
}

$invoice_id = (int)$_GET['id'];
$supplierInvoice = new SupplierInvoice();

// Get invoice data
$invoice_data = $supplierInvoice->getById($invoice_id);
if(!$invoice_data) {
    die('Invoice not found');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Invoice #<?php echo htmlspecialchars($invoice_data['invoice_number']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none;
            }
            .page-break {
                page-break-before: always;
            }
        }
        body {
            background: #fff;
            font-size: 14px;
        }
        .invoice-header {
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .invoice-details {
            margin: 40px 0;
        }
        .table th {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="no-print mb-4">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print Invoice
            </button>
            <a href="view.php?id=<?php echo $invoice_id; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <div class="invoice-header">
            <div class="row">
                <div class="col-md-6">
                    <h5>Supplier:</h5>
                    <p><strong><?php echo htmlspecialchars($invoice_data['supplier_name']); ?></strong></p>
                </div>
                <div class="col-md-6 text-end">
                    <h4>SUPPLIER INVOICE</h4>
                    <p><strong>Invoice #:</strong> <?php echo htmlspecialchars($invoice_data['invoice_number']); ?></p>
                    <p><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($invoice_data['invoice_date'])); ?></p>
                    <p><strong>Due Date:</strong> <?php echo date('d/m/Y', strtotime($invoice_data['due_date'])); ?></p>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">GST</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($invoice_data['items'] as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td class="text-center"><?php echo $item['quantity']; ?></td>
                            <td class="text-end">$<?php echo number_format($item['unit_price'], 2); ?></td>
                            <td class="text-end">$<?php echo number_format($item['tax_amount'], 2); ?></td>
                            <td class="text-end">$<?php echo number_format($item['subtotal'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                        <td class="text-end">$<?php echo number_format($invoice_data['subtotal'], 2); ?></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-end"><strong>GST:</strong></td>
                        <td class="text-end">$<?php echo number_format($invoice_data['tax_amount'], 2); ?></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-end"><strong>Total Amount:</strong></td>
                        <td class="text-end"><strong>$<?php echo number_format($invoice_data['total_amount'], 2); ?></strong></td>
                    </tr>
                    <?php if($invoice_data['payment_due'] > 0): ?>
                        <tr>
                            <td colspan="4" class="text-end"><strong>Amount Due:</strong></td>
                            <td class="text-end"><strong>$<?php echo number_format($invoice_data['payment_due'], 2); ?></strong></td>
                        </tr>
                    <?php endif; ?>
                </tfoot>
            </table>
        </div>

        <?php if($invoice_data['notes']): ?>
            <div class="mt-4">
                <h5>Notes:</h5>
                <p><?php echo nl2br(htmlspecialchars($invoice_data['notes'])); ?></p>
            </div>
        <?php endif; ?>

        <?php if(!empty($invoice_data['payments'])): ?>
            <div class="mt-4">
                <h5>Payment History:</h5>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($invoice_data['payments'] as $payment): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?></td>
                                <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                <td><?php echo htmlspecialchars($payment['reference_number']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
