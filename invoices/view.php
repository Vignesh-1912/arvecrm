<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = (int) $_GET['id'];

// Get invoice
$stmt = $conn->prepare("SELECT
    i.*,
    co.company_name,
    CONCAT(COALESCE(ct.first_name, ''), ' ', COALESCE(ct.last_name, '')) AS contact_name,
    cu.customer_code,
    u.name AS created_by_name
FROM invoices i
LEFT JOIN companies co ON co.id = i.company_id
LEFT JOIN contacts ct ON ct.id = i.contact_id
LEFT JOIN customers cu ON cu.id = i.customer_id
LEFT JOIN users u ON u.id = i.created_by
WHERE i.id = :id");

$stmt->execute([':id' => $id]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    header('Location: index.php');
    exit;
}

// Get items
$stmt = $conn->prepare("SELECT ii.*, p.name AS product_name, p.sku FROM invoice_items ii LEFT JOIN products p ON ii.product_id = p.id WHERE ii.invoice_id = :invoice_id ORDER BY ii.id ASC");
$stmt->execute([':invoice_id' => $id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$items_subtotal = 0;
foreach ($items as $it) {
    $items_subtotal += (float) ($it['total'] ?? 0);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($invoice['invoice_number']); ?> - Invoice</title>
    <link rel="stylesheet" href="/crm/assets/css/sidebar.css">
    <style>
        body { font-family:Arial, sans-serif; margin:0; color:#172033; background:#f5f7fb }
        .main-content { padding:28px; margin-left:250px }
        .invoice-card { background:#fff; border:1px solid #e2e8f0; padding:20px; border-radius:12px }
        .invoice-header { display:flex; justify-content:space-between; align-items:flex-start }
        .invoice-meta { text-align:right }
        .invoice-table { width:100%; border-collapse:collapse; margin-top:14px }
        .invoice-table th, .invoice-table td { padding:10px 12px; border-bottom:1px solid #eef2f7; }
        .btn { padding:8px 12px; border-radius:8px; border:1px solid #dbe3ee; background:#fff; color:#475569; text-decoration:none; }
        .btn.primary { background:#2563eb; color:#fff; border-color:#2563eb; }
        .print-only-hide { display:inline-block }
        @media print {
            .print-only-hide { display:none !important }
            .main-content { margin-left:0 !important }
        }
    </style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="invoice-card">
        <div class="invoice-header">
            <div>
                <h2>Invoice</h2>
                <div><?php echo htmlspecialchars($invoice['company_name'] ?? ''); ?></div>
                <div><?php echo htmlspecialchars($invoice['customer_code'] ?? ''); ?></div>
                <div><?php echo htmlspecialchars($invoice['contact_name'] ?? ''); ?></div>
            </div>

            <div class="invoice-meta">
                <div><strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong></div>
                <div><?php echo htmlspecialchars($invoice['invoice_date']); ?></div>
                <div>Status: <?php echo htmlspecialchars($invoice['invoice_status'] ?? ''); ?></div>
                <div style="margin-top:8px">
                    <a class="btn" href="edit.php?id=<?php echo $id; ?>">Edit</a>
                    <a class="btn primary" id="printBtn" href="#">Print</a>
                </div>
            </div>
        </div>

        <table class="invoice-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Description</th>
                    <th>Qty</th>
                    <th>Unit</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($items) === 0): ?>
                    <tr><td colspan="5" style="color:#64748b; padding:18px">No items added to this invoice.</td></tr>
                <?php else: ?>
                    <?php foreach ($items as $i => $it): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo htmlspecialchars($it['description'] ?? $it['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($it['quantity'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars(number_format((float)($it['unit_price'] ?? 0), 2)); ?></td>
                            <td><?php echo htmlspecialchars(number_format((float)($it['total'] ?? 0), 2)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div style="display:flex; justify-content:flex-end; gap:14px; margin-top:12px">
            <div style="width:320px; background:#fff; padding:12px; border-radius:8px; border:1px solid #eef2f7">
                <div style="display:flex; justify-content:space-between"><div>Subtotal</div><div><?php echo number_format($items_subtotal, 2); ?></div></div>
                <div style="display:flex; justify-content:space-between"><div>Tax</div><div><?php echo number_format((float)($invoice['tax_amount'] ?? 0), 2); ?></div></div>
                <div style="display:flex; justify-content:space-between"><div>Discount</div><div><?php echo number_format((float)($invoice['discount_amount'] ?? 0), 2); ?></div></div>
                <hr>
                <div style="display:flex; justify-content:space-between; font-weight:700"><div>Total</div><div><?php echo number_format((float)($invoice['total_amount'] ?? 0), 2); ?></div></div>
            </div>
        </div>

        <?php if (!empty($invoice['notes'])): ?>
            <div style="margin-top:12px; color:#64748b">Notes: <?php echo htmlspecialchars($invoice['notes']); ?></div>
        <?php endif; ?>

    </div>
</div>

<script>
    document.getElementById('printBtn').addEventListener('click', function (e) {
        e.preventDefault();
        window.print();
    });
</script>

</body>
</html>