<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/pos/config/company.php';
?>
<div class="invoice-header mb-4">
    <div class="row">
        <div class="col-6">
            <h2><?php echo COMPANY_NAME; ?></h2>
            <p class="mb-1"><?php echo COMPANY_ADDRESS; ?></p>
            <p class="mb-1"><?php echo COMPANY_CITY . ' ' . COMPANY_STATE . ' ' . COMPANY_POSTCODE; ?></p>
            <p class="mb-1">Phone: <?php echo COMPANY_PHONE; ?></p>
            <p class="mb-1">Email: <?php echo COMPANY_EMAIL; ?></p>
            <p class="mb-1">Web: <?php echo COMPANY_WEBSITE; ?></p>
            <p class="mb-1">ABN: <?php echo COMPANY_ABN; ?></p>
        </div>
        <div class="col-6 text-end">
            <h1 class="text-uppercase"><?php echo $document_type ?? 'Tax Invoice'; ?></h1>
            <p class="mb-1">Invoice #: <?php echo htmlspecialchars($invoice_data['invoice_number']); ?></p>
            <p class="mb-1">Date: <?php echo date('d/m/Y', strtotime($invoice_data['invoice_date'])); ?></p>
            <p class="mb-1">Due Date: <?php echo date('d/m/Y', strtotime($invoice_data['due_date'])); ?></p>
        </div>
    </div>
</div>
