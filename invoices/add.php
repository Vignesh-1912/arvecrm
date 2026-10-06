<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$error = "";

// Default values
$invoice_number = "INV-" . date("Ymd-His");
$customer_id = null;
$company_id = null;
$contact_id = null;
$subtotal = 0;
$tax_amount = 0;
$discount_amount = 0;
$total_amount = 0;
$status = 'draft';
$invoice_date = date('Y-m-d');
$notes = '';

// Load lists for selects
$companies = $conn->query("SELECT id, company_name FROM companies ORDER BY company_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$customers = $conn->query("SELECT id, customer_code FROM customers ORDER BY customer_code ASC")->fetchAll(PDO::FETCH_ASSOC);
$contacts = $conn->query("SELECT id, first_name, last_name FROM contacts ORDER BY first_name ASC, last_name ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoice_number = trim($_POST['invoice_number'] ?? $invoice_number);
    $company_id = !empty($_POST['company_id']) ? (int) $_POST['company_id'] : null;
    $customer_id = !empty($_POST['customer_id']) ? (int) $_POST['customer_id'] : null;
    $contact_id = !empty($_POST['contact_id']) ? (int) $_POST['contact_id'] : null;
    $tax_amount = is_numeric($_POST['tax_amount'] ?? '') ? (float) $_POST['tax_amount'] : 0;
    $discount_amount = is_numeric($_POST['discount_amount'] ?? '') ? (float) $_POST['discount_amount'] : 0;
    $invoice_date = !empty($_POST['invoice_date']) ? $_POST['invoice_date'] : date('Y-m-d');
    $notes = trim($_POST['notes'] ?? '');

    // Basic validation
    if ($invoice_number === '') {
        $error = 'Invoice number is required.';
    } else {
        try {
            $total_amount = max(0, $subtotal - $discount_amount + $tax_amount);

            $sql = "INSERT INTO invoices (
                invoice_number,
                company_id,
                customer_id,
                contact_id,
                subtotal,
                tax_amount,
                discount_amount,
                total_amount,
                invoice_status,
                invoice_date,
                notes,
                created_by
            ) VALUES (
                :invoice_number,
                :company_id,
                :customer_id,
                :contact_id,
                :subtotal,
                :tax_amount,
                :discount_amount,
                :total_amount,
                :invoice_status,
                :invoice_date,
                :notes,
                :created_by
            )";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':invoice_number' => $invoice_number,
                ':company_id' => $company_id,
                ':customer_id' => $customer_id,
                ':contact_id' => $contact_id,
                ':subtotal' => $subtotal,
                ':tax_amount' => $tax_amount,
                ':discount_amount' => $discount_amount,
                ':total_amount' => $total_amount,
                ':invoice_status' => $status,
                ':invoice_date' => $invoice_date,
                ':notes' => $notes,
                ':created_by' => $_SESSION['user_id']
            ]);

            $invoice_id = $conn->lastInsertId();

            header('Location: view.php?id=' . $invoice_id);
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = 'Invoice number already exists.';
            } else {
                $error = 'Unable to create invoice.';
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Invoice - CRM</title>
    <link rel="stylesheet" href="/crm/assets/css/sidebar.css">
    <style>
        .main-content { margin-left:250px; padding:28px; }
        .form-card { background:#fff; border:1px solid #e2e8f0; padding:18px; border-radius:12px; }
        label { display:block; margin-bottom:6px; font-weight:700; font-size:13px; }
        input, select, textarea { width:100%; padding:8px 10px; border:1px solid #dbe3ee; border-radius:8px; }
        .actions { margin-top:12px; }
        .btn { padding:8px 12px; border-radius:8px; border:1px solid #dbe3ee; background:#fff; color:#475569; text-decoration:none; }
        .btn.primary { background:#2563eb; color:#fff; border-color:#2563eb; }
    </style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
    <h1>Create Invoice</h1>
    <?php if ($error !== ''): ?>
        <div style="color:#b91c1c; margin-bottom:12px;"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="form-card">
        <form method="post" action="add.php">
            <div>
                <label>Invoice Number</label>
                <input name="invoice_number" value="<?php echo htmlspecialchars($invoice_number); ?>">
            </div>

            <div style="display:flex; gap:12px; margin-top:12px;">
                <div style="flex:1;">
                    <label>Company</label>
                    <select name="company_id">
                        <option value="">-- Select --</option>
                        <?php foreach ($companies as $c): ?>
                            <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['company_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="flex:1;">
                    <label>Customer</label>
                    <select name="customer_id">
                        <option value="">-- Select --</option>
                        <?php foreach ($customers as $cu): ?>
                            <option value="<?php echo (int)$cu['id']; ?>"><?php echo htmlspecialchars($cu['customer_code']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="flex:1;">
                    <label>Contact</label>
                    <select name="contact_id">
                        <option value="">-- Select --</option>
                        <?php foreach ($contacts as $ct): ?>
                            <option value="<?php echo (int)$ct['id']; ?>"><?php echo htmlspecialchars($ct['first_name'] . ' ' . ($ct['last_name'] ?? '')); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display:flex; gap:12px; margin-top:12px;">
                <div style="flex:1;">
                    <label>Tax Amount</label>
                    <input name="tax_amount" value="<?php echo htmlspecialchars($tax_amount); ?>">
                </div>
                <div style="flex:1;">
                    <label>Discount Amount</label>
                    <input name="discount_amount" value="<?php echo htmlspecialchars($discount_amount); ?>">
                </div>
                <div style="flex:1;">
                    <label>Invoice Date</label>
                    <input type="date" name="invoice_date" value="<?php echo htmlspecialchars($invoice_date); ?>">
                </div>
            </div>

            <div style="margin-top:12px;">
                <label>Notes</label>
                <textarea name="notes" rows="4"><?php echo htmlspecialchars($notes); ?></textarea>
            </div>

            <div class="actions">
                <button class="btn primary" type="submit">Create Invoice</button>
                <a class="btn" href="index.php">Cancel</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>