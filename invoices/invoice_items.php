<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

if (!isset($_GET['invoice_id']) || !is_numeric($_GET['invoice_id'])) {
    header('Location: index.php');
    exit;
}

$invoice_id = (int) $_GET['invoice_id'];

$error = '';

// Add item
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = !empty($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
    $quantity = is_numeric($_POST['quantity'] ?? '') ? (float) $_POST['quantity'] : 0;
    $unit_price = is_numeric($_POST['unit_price'] ?? '') ? (float) $_POST['unit_price'] : 0;
    $discount = is_numeric($_POST['discount'] ?? '') ? (float) $_POST['discount'] : 0;
    $tax = is_numeric($_POST['tax'] ?? '') ? (float) $_POST['tax'] : 0;

    if ($product_id <= 0) {
        $error = 'Please select a product.';
    } elseif ($quantity <= 0) {
        $error = 'Quantity must be greater than 0.';
    } else {
        // get product
        $stmt = $conn->prepare("SELECT id, name, price FROM products WHERE id = :id AND status = 1");
        $stmt->execute([':id' => $product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $error = 'Product not found or inactive.';
        } else {
            $description = $product['name'];
            $subtotal = $quantity * $unit_price;
            $total = $subtotal - $discount + $tax;
            if ($total < 0) $total = 0;

            $stmt = $conn->prepare("INSERT INTO invoice_items (invoice_id, product_id, description, quantity, unit_price, discount, tax, total) VALUES (:invoice_id, :product_id, :description, :quantity, :unit_price, :discount, :tax, :total)");
            $stmt->execute([
                ':invoice_id' => $invoice_id,
                ':product_id' => $product_id,
                ':description' => $description,
                ':quantity' => $quantity,
                ':unit_price' => $unit_price,
                ':discount' => $discount,
                ':tax' => $tax,
                ':total' => $total
            ]);

            // Recalculate invoice totals
            $stmt = $conn->prepare("SELECT COALESCE(SUM(total),0) AS subtotal FROM invoice_items WHERE invoice_id = :invoice_id");
            $stmt->execute([':invoice_id' => $invoice_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $new_subtotal = (float) ($row['subtotal'] ?? 0);

            $stmt = $conn->prepare("UPDATE invoices SET subtotal = :subtotal, total_amount = (:subtotal - COALESCE(discount_amount,0) + COALESCE(tax_amount,0)) WHERE id = :id");
            $stmt->execute([':subtotal' => $new_subtotal, ':id' => $invoice_id]);

            header('Location: invoice_items.php?invoice_id=' . $invoice_id);
            exit;
        }
    }
}

// Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $item_id = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM invoice_items WHERE id = :id AND invoice_id = :invoice_id");
    $stmt->execute([':id' => $item_id, ':invoice_id' => $invoice_id]);

    // recalc
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total),0) AS subtotal FROM invoice_items WHERE invoice_id = :invoice_id");
    $stmt->execute([':invoice_id' => $invoice_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $new_subtotal = (float) ($row['subtotal'] ?? 0);
    $stmt = $conn->prepare("UPDATE invoices SET subtotal = :subtotal, total_amount = (:subtotal - COALESCE(discount_amount,0) + COALESCE(tax_amount,0)) WHERE id = :id");
    $stmt->execute([':subtotal' => $new_subtotal, ':id' => $invoice_id]);

    header('Location: invoice_items.php?invoice_id=' . $invoice_id);
    exit;
}

// Load items & products
$stmt = $conn->prepare("SELECT ii.*, p.name AS product_name FROM invoice_items ii LEFT JOIN products p ON ii.product_id = p.id WHERE ii.invoice_id = :invoice_id ORDER BY ii.id ASC");
$stmt->execute([':invoice_id' => $invoice_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$products = $conn->query("SELECT id, name, price FROM products WHERE status = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice Items</title>
    <link rel="stylesheet" href="/crm/assets/css/sidebar.css">
    <style>
        .main-content { margin-left:250px; padding:28px }
        .card { background:#fff; border:1px solid #e2e8f0; padding:12px; border-radius:10px }
        table { width:100%; border-collapse:collapse }
        th, td { padding:8px 10px; border-bottom:1px solid #eef2f7 }
    </style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
    <h1>Invoice Items</h1>

    <?php if ($error !== ''): ?>
        <div style="color:#b91c1c"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card">
        <form method="post" action="invoice_items.php?invoice_id=<?php echo $invoice_id; ?>">
            <div style="display:flex; gap:8px; align-items:center">
                <select name="product_id">
                    <option value="">-- Select Product --</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?php echo (int)$p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <input name="quantity" placeholder="Qty" style="width:80px">
                <input name="unit_price" placeholder="Unit price" style="width:120px">
                <input name="discount" placeholder="Discount" style="width:100px">
                <input name="tax" placeholder="Tax" style="width:100px">
                <button class="btn" type="submit">Add</button>
            </div>
        </form>

        <table style="margin-top:12px">
            <thead>
                <tr><th>#</th><th>Desc</th><th>Qty</th><th>Unit</th><th>Total</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php if (count($items) === 0): ?>
                    <tr><td colspan="6" style="color:#64748b; padding:10px">No items</td></tr>
                <?php else: ?>
                    <?php foreach ($items as $i => $it): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo htmlspecialchars($it['description'] ?? $it['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($it['quantity']); ?></td>
                            <td><?php echo number_format((float)$it['unit_price'], 2); ?></td>
                            <td><?php echo number_format((float)$it['total'], 2); ?></td>
                            <td><a class="btn" href="invoice_items.php?invoice_id=<?php echo $invoice_id; ?>&delete=<?php echo (int)$it['id']; ?>" onclick="return confirm('Remove item?')">Remove</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top:12px"><a class="btn" href="view.php?id=<?php echo $invoice_id; ?>">Back to Invoice</a></div>
</div>
</body>
</html>