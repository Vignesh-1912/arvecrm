<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$stmt = $conn->query("SELECT
    i.id,
    i.invoice_number,
    i.total_amount,
    i.invoice_date,
    i.invoice_status,
    c.customer_code,
    co.company_name,
    CONCAT(COALESCE(ct.first_name, ''), ' ', COALESCE(ct.last_name, '')) AS contact_name
FROM invoices i
LEFT JOIN customers c ON c.id = i.customer_id
LEFT JOIN companies co ON co.id = i.company_id
LEFT JOIN contacts ct ON ct.id = i.contact_id
ORDER BY i.id DESC");

$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices - CRM</title>
    <link rel="stylesheet" href="/crm/assets/css/sidebar.css">
    <style>
        .main-content { margin-left: 250px; padding: 28px; }
        .page-header { display:flex; justify-content:space-between; align-items:center; background:#fff; padding:18px; border:1px solid #e2e8f0; border-radius:12px; }
        table { width:100%; border-collapse:collapse; background:#fff; border:1px solid #e2e8f0; }
        th, td { padding:10px 12px; border-bottom:1px solid #eef2f7; text-align:left; }
        .actions a { margin-right:8px; }
        .btn { padding:8px 12px; border-radius:8px; border:1px solid #dbe3ee; background:#fff; color:#475569; text-decoration:none; }
        .btn.primary { background:#2563eb; color:#fff; border-color:#2563eb; }
    </style>
</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">
    <div class="page-header">
        <div>
            <h1>Invoices</h1>
            <p>Manage invoices and billing for customers.</p>
        </div>
        <div>
            <a class="btn" href="add.php">+ New Invoice</a>
        </div>
    </div>

    <div style="margin-top:18px;">
        <table>
            <thead>
                <tr>
                    <th>S.No</th>
                    <th>Invoice #</th>
                    <th>Company</th>
                    <th>Customer</th>
                    <th>Contact</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($invoices) === 0): ?>
                    <tr><td colspan="9" style="color:#64748b; padding:18px;">No invoices found.</td></tr>
                <?php else: ?>
                    <?php foreach ($invoices as $index => $invoice): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                            <td><?php echo htmlspecialchars($invoice['company_name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($invoice['customer_code'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($invoice['contact_name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($invoice['invoice_date'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars(number_format((float)($invoice['total_amount'] ?? 0), 2)); ?></td>
                            <td><?php echo htmlspecialchars($invoice['invoice_status'] ?? ''); ?></td>
                            <td class="actions">
                                <a class="btn" href="view.php?id=<?php echo (int) $invoice['id']; ?>">View</a>
                                <a class="btn" href="edit.php?id=<?php echo (int) $invoice['id']; ?>">Edit</a>
                                <a class="btn" href="delete.php?id=<?php echo (int) $invoice['id']; ?>" onclick="return confirm('Delete this invoice?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

</body>

</html>