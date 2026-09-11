<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

/* =========================
   DATE FILTER
========================= */

$from_date = $_GET["from_date"] ?? "";
$to_date = $_GET["to_date"] ?? "";

$where = [];
$params = [];

if (!empty($from_date)) {
    $where[] = "DATE(sales.sale_date) >= :from_date";
    $params[":from_date"] = $from_date;
}

if (!empty($to_date)) {
    $where[] = "DATE(sales.sale_date) <= :to_date";
    $params[":to_date"] = $to_date;
}

$where_sql = "";

if (!empty($where)) {
    $where_sql = " WHERE " . implode(" AND ", $where);
}

/* =========================
   GET SALES
========================= */

$sql = "
    SELECT
        sales.id,
        sales.sale_number,
        customers.customer_code,
        companies.company_name,
        contacts.first_name,
        contacts.last_name,
        sales.subtotal,
        sales.tax_amount,
        sales.discount_amount,
        sales.total_amount,
        sales.payment_status,
        sales.sale_status,
        sales.sale_date
    FROM sales

    LEFT JOIN customers
        ON sales.customer_id = customers.id

    LEFT JOIN companies
        ON sales.company_id = companies.id

    LEFT JOIN contacts
        ON sales.contact_id = contacts.id

    $where_sql

    ORDER BY sales.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   CSV HEADERS
========================= */

$filename = "sales_report";

if (!empty($from_date) && !empty($to_date)) {

    $filename .= "_" .
        $from_date .
        "_to_" .
        $to_date;

} elseif (!empty($from_date)) {

    $filename .= "_from_" . $from_date;

} elseif (!empty($to_date)) {

    $filename .= "_until_" . $to_date;
}

$filename .= ".csv";

header("Content-Type: text/csv; charset=utf-8");
header(
    "Content-Disposition: attachment; filename=\"$filename\""
);
header("Pragma: no-cache");
header("Expires: 0");

/* =========================
   CREATE CSV
========================= */

$output = fopen("php://output", "w");

/* UTF-8 BOM for Excel */

fprintf(
    $output,
    chr(0xEF) . chr(0xBB) . chr(0xBF)
);

/* =========================
   CSV HEADER ROW
========================= */

fputcsv($output, [
    "ID",
    "Sale Number",
    "Customer Code",
    "Company",
    "Contact",
    "Subtotal",
    "Tax Amount",
    "Discount Amount",
    "Total Amount",
    "Payment Status",
    "Sale Status",
    "Sale Date"
]);

/* =========================
   CSV DATA
========================= */

foreach ($sales as $sale) {

    $contact_name = "";

    if (!empty($sale["first_name"])) {

        $contact_name = trim(
            $sale["first_name"] .
            " " .
            ($sale["last_name"] ?? "")
        );
    }

    fputcsv($output, [

        $sale["id"],

        $sale["sale_number"],

        $sale["customer_code"] ?? "",

        $sale["company_name"] ?? "",

        $contact_name,

        $sale["subtotal"],

        $sale["tax_amount"],

        $sale["discount_amount"],

        $sale["total_amount"],

        $sale["payment_status"],

        $sale["sale_status"],

        $sale["sale_date"]

    ]);
}

fclose($output);

exit;
?>