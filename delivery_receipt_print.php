<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'includes/db.php';
include 'includes/auth.php';

$id = (int)$_GET['id'];
$copy_type = isset($_GET['copy']) ? $_GET['copy'] : 'company';

// 1. Get delivery receipt data
$receipt = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT dr.*, u.username as prepared_by_name 
     FROM delivery_receipts dr
     LEFT JOIN users u ON dr.prepared_by = u.id
     WHERE dr.delivery_number = $id"
));
if (!$receipt) {
    die('Delivery receipt not found');
}

// 2. Get delivered items, join to items and master_items for full info
$items = [];
$result = mysqli_query($conn, "
    SELECT 
        di.*,
        COALESCE(mi.name, i.name, '[No Name]') AS item_name,
        COALESCE(mi.description, i.description, di.item_description, '[No Description]') AS item_description,
        COALESCE(mi.sku, i.sku, '[No SKU]') AS item_sku
    FROM delivered_items di
    LEFT JOIN items i ON di.item_id = i.id
    LEFT JOIN master_items mi ON i.master_item_id = mi.id
    WHERE di.delivery_number = $id
");
while ($row = mysqli_fetch_assoc($result)) {
    $items[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Delivery Receipt #<?= $receipt['delivery_number'] ?></title>
    <style>
        body { font-family: 'Arial', sans-serif; margin: 0; padding: 5mm; color: #333; font-size: 12px; }
        .receipt-container { max-width: 210mm; margin: 0 auto; border: 1px solid #1a5276; padding: 5mm; background-color: white; }
        .header { text-align: center; margin-bottom: 5mm; padding-bottom: 3mm; }
        .logo-container { display: flex; align-items: center; justify-content: center; margin-bottom: 2mm; }
        .logo { height: 20mm; max-width: 50mm; }
        .header-text { margin-left: 5mm; text-align: left; }
        .company-name { font-size: 14px; font-weight: bold; color: #1a5276; margin: 0; }
        .company-address { font-size: 10px; margin: 1mm 0; }
        .copy-label { font-size: 12px; margin: 2mm 0; padding: 2mm 5mm; background-color: #1a5276; color: white; display: inline-block; border-radius: 2px; }
        .receipt-title { font-size: 14px; margin: 2mm 0; color: #1a5276; text-transform: uppercase; }
        .receipt-info { margin-bottom: 5mm; padding: 3mm; border-radius: 2px; border: 1px solid #ddd; }
        .info-row { display: flex; margin-bottom: 1mm; }
        .info-label { width: 30mm; font-weight: bold; color: #1a5276; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 5mm; font-size: 11px; }
        .items-table th { background-color: #1a5276; color: white; padding: 2mm; text-align: left; font-weight: bold; font-size: 11px; }
        .items-table td { padding: 2mm; border-bottom: 1px solid #ddd; }
        .items-table tr:nth-child(even) { background-color: #f2f2f2; }
        .total-row { font-weight: bold; background-color: #d4e6f1 !important; }
        .signature-area { margin-top: 10mm; display: flex; justify-content: space-between; }
        .signature-box { width: 60mm; border-top: 1px solid #1a5276; text-align: center; padding-top: 2mm; font-size: 11px; }
        .footer { margin-top: 5mm; font-size: 9px; text-align: center; color: #666; padding-top: 2mm; line-height: 1.3; }
        .footer strong { color: #1a5276; }
        @media print {
            body { padding: 0; margin: 0; background: none; font-size: 11px; }
            .receipt-container { border: none; padding: 0; max-width: 100%; box-shadow: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="header">
            <div class="logo-container">
                <img src="assets/terralogix2.png" alt="Terralogix Logo" class="logo">
                <div class="header-text">
                    <div class="company-name">TERRALOGIX CORPORATION</div>
                    <div class="company-address">Door 103, LMGG Building, Mabini-Avanceña St., Davao City</div>
                </div>
            </div>
            <div class="copy-label"><?= strtoupper($copy_type) ?> COPY</div>
            <div class="receipt-title">DELIVERY RECEIPT</div>
        </div>
        <div class="receipt-info">
            <div class="info-row"><div class="info-label">Delivery Number:</div><div><?= htmlspecialchars($receipt['delivery_number']) ?></div></div>
            <div class="info-row"><div class="info-label">Delivery Date:</div><div><?= date('F j, Y', strtotime($receipt['date'])) ?></div></div>
            <div class="info-row"><div class="info-label">Company Name:</div><div><?= htmlspecialchars($receipt['client']) ?></div></div>
            <div class="info-row"><div class="info-label">Project:</div><div><?= htmlspecialchars($receipt['project']) ?></div></div>
            <div class="info-row"><div class="info-label">Address:</div><div><?= htmlspecialchars($receipt['location']) ?></div></div>
        </div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 25%">Item Name</th>
                    <th style="width: 30%">Description</th>
                    <th style="width: 15%">Serial No.</th>
                    <th style="width: 8%">Ordered Qty</th>
                    <th style="width: 8%">Delivered Qty</th>
                    <th style="width: 8%">Outstanding</th>
                    <th style="width: 8%">Unit</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                        <td><?= htmlspecialchars($item['item_description']) ?></td>
                        <td><?= htmlspecialchars($item['item_sku']) ?></td>
                        <td><?= htmlspecialchars($item['ordered']) ?></td>
                        <td><?= htmlspecialchars($item['delivered']) ?></td>
                        <td><?= htmlspecialchars($item['outstanding']) ?></td>
                        <td><?= htmlspecialchars($item['unit']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="4"><strong>TOTAL QUANTITY DELIVERED</strong></td>
                    <td><strong><?= htmlspecialchars($receipt['total_quantity']) ?></strong></td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
        <div class="signature-area">
            <div class="signature-box">
                Prepared by:<br>
                <?= htmlspecialchars($receipt['prepared_by_name']) ?><br>
                <span style="font-weight: normal;">(Signature over printed name)</span>
            </div>
            <div class="signature-box">
                Checked & Received by:<br>
                <?= htmlspecialchars($receipt['received_by']) ?><br>
                <span style="font-weight: normal;">(Signature over printed name)</span>
            </div>
        </div>
        <div class="footer">
            <p><strong>TERRALOGIX DELIVERY RECEIPT #<?= $receipt['delivery_number'] ?></strong> | Generated on <?= date('m/d/Y h:i A') ?></p>
            <p>Any shortage/damage must be notified within 24 hours. Complaints accepted in writing within 30 days.</p>
            <p>No returns without prior authorization. Contact: 082-3312456, 0919 074 0758 | info@terralogixcorp.com</p>
        </div>
    </div>
    <?php if ($copy_type === 'company'): ?>
        <div class="page-break"></div>
        <script>
            window.onload = function() {
                window.print();
                setTimeout(function() {
                    window.location.href = 'delivery_receipt_print.php?id=<?= $id ?>&copy=customer';
                }, 1000);
            };
        </script>
    <?php else: ?>
        <script>
            window.onload = function() {
                window.print();
                setTimeout(function() {
                    window.close();
                }, 1000);
            };
        </script>
    <?php endif; ?>
</body>
</html>
