<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$from_date = $_GET["from_date"] ?? "";
$to_date = $_GET["to_date"] ?? "";

$where = [];
$params = [];

if (!empty($from_date)) {
    $where[] = "DATE(quotes.created_at) >= :from_date";
    $params[":from_date"] = $from_date;
}

if (!empty($to_date)) {
    $where[] = "DATE(quotes.created_at) <= :to_date";
    $params[":to_date"] = $to_date;
}

$where_sql = "";

if (!empty($where)) {
    $where_sql = " WHERE " . implode(" AND ", $where);
}

$sql = "
    SELECT
        quotes.id,
        quotes.quote_number,
        customers.customer_code,
        companies.company_name,
        contacts.first_name,
        contacts.last_name,
        deals.title AS deal_title,
        quotes.subtotal,
        quotes.tax_amount,
        quotes.discount_amount,
        quotes.total_amount,
        quotes.status,
        quotes.valid_until,
        quotes.created_at,
        quotes.notes
    FROM quotes

    LEFT JOIN customers
        ON quotes.customer_id = customers.id

    LEFT JOIN companies
        ON quotes.company_id = companies.id

    LEFT JOIN contacts
        ON quotes.contact_id = contacts.id

    LEFT JOIN deals
        ON quotes.deal_id = deals.id

    $where_sql

    ORDER BY quotes.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$filename = "quotes_report";

if (!empty($from_date) && !empty($to_date)) {
    $filename .= "_" . $from_date . "_to_" . $to_date;
} elseif (!empty($from_date)) {
    $filename .= "_from_" . $from_date;
} elseif (!empty($to_date)) {
    $filename .= "_until_" . $to_date;
}

$filename .= ".csv";

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

$output = fopen("php://output", "w");

fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($output, [
    "ID",
    "Quote Number",
    "Customer Code",
    "Company",
    "Contact",
    "Deal",
    "Subtotal",
    "Tax Amount",
    "Discount Amount",
    "Total Amount",
    "Status",
    "Valid Until",
    "Created Date",
    "Notes"
]);

foreach ($quotes as $quote) {

    $contact_name = "";

    if (!empty($quote["first_name"])) {
        $contact_name = trim(
            $quote["first_name"] . " " . ($quote["last_name"] ?? "")
        );
    }

    fputcsv($output, [
        $quote["id"],
        $quote["quote_number"],
        $quote["customer_code"] ?? "",
        $quote["company_name"] ?? "",
        $contact_name,
        $quote["deal_title"] ?? "",
        $quote["subtotal"],
        $quote["tax_amount"],
        $quote["discount_amount"],
        $quote["total_amount"],
        $quote["status"],
        $quote["valid_until"] ?? "",
        $quote["created_at"],
        $quote["notes"] ?? ""
    ]);
}

fclose($output);
exit;
?>