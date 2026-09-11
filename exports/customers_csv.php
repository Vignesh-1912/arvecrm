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
    $where[] = "DATE(customers.created_at) >= :from_date";
    $params[":from_date"] = $from_date;
}

if (!empty($to_date)) {
    $where[] = "DATE(customers.created_at) <= :to_date";
    $params[":to_date"] = $to_date;
}

$where_sql = "";

if (!empty($where)) {
    $where_sql = " WHERE " . implode(" AND ", $where);
}

$sql = "
    SELECT
        customers.id,
        customers.customer_code,
        customers.customer_type,
        customers.status,
        customers.credit_limit,
        customers.notes,
        companies.company_name,
        contacts.first_name,
        contacts.last_name,
        contacts.email,
        contacts.phone,
        customers.created_at
    FROM customers

    LEFT JOIN companies
        ON customers.company_id = companies.id

    LEFT JOIN contacts
        ON customers.contact_id = contacts.id

    $where_sql

    ORDER BY customers.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$filename = "customers_report";

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
    "Customer Code",
    "Customer Type",
    "Status",
    "Credit Limit",
    "Company",
    "Contact",
    "Email",
    "Phone",
    "Notes",
    "Created Date"
]);

foreach ($customers as $customer) {

    $contact_name = "";

    if (!empty($customer["first_name"])) {
        $contact_name = trim(
            $customer["first_name"] . " " . ($customer["last_name"] ?? "")
        );
    }

    fputcsv($output, [
        $customer["id"],
        $customer["customer_code"] ?? "",
        $customer["customer_type"] ?? "",
        $customer["status"] ?? "",
        $customer["credit_limit"] ?? "",
        $customer["company_name"] ?? "",
        $contact_name,
        $customer["email"] ?? "",
        $customer["phone"] ?? "",
        $customer["notes"] ?? "",
        $customer["created_at"]
    ]);
}

fclose($output);
exit;
?>