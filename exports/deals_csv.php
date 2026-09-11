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
    $where[] = "DATE(deals.expected_close_date) >= :from_date";
    $params[":from_date"] = $from_date;
}

if (!empty($to_date)) {
    $where[] = "DATE(deals.expected_close_date) <= :to_date";
    $params[":to_date"] = $to_date;
}

$where_sql = "";

if (!empty($where)) {
    $where_sql = " WHERE " . implode(" AND ", $where);
}

/* =========================
   GET DEALS
========================= */

$sql = "
    SELECT
        deals.id,
        deals.title,
        companies.company_name,
        contacts.first_name,
        contacts.last_name,
        deals.amount,
        deals.stage,
        deals.probability,
        deals.expected_close_date,
        users.name AS assigned_user,
        deals.description
    FROM deals

    LEFT JOIN companies
        ON deals.company_id = companies.id

    LEFT JOIN contacts
        ON deals.contact_id = contacts.id

    LEFT JOIN users
        ON deals.assigned_to = users.id

    $where_sql

    ORDER BY deals.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$deals = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   FILE NAME
========================= */

$filename = "deals_report";

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

/* =========================
   CSV HEADERS
========================= */

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
   CSV HEADER
========================= */

fputcsv($output, [
    "ID",
    "Deal Title",
    "Company",
    "Contact",
    "Amount",
    "Stage",
    "Probability",
    "Expected Close Date",
    "Assigned To",
    "Description"
]);

/* =========================
   CSV DATA
========================= */

foreach ($deals as $deal) {

    $contact_name = "";

    if (!empty($deal["first_name"])) {

        $contact_name = trim(
            $deal["first_name"] .
            " " .
            ($deal["last_name"] ?? "")
        );
    }

    fputcsv($output, [

        $deal["id"],

        $deal["title"],

        $deal["company_name"] ?? "",

        $contact_name,

        $deal["amount"],

        $deal["stage"],

        $deal["probability"] . "%",

        $deal["expected_close_date"] ?? "",

        $deal["assigned_user"] ?? "",

        $deal["description"] ?? ""

    ]);
}

fclose($output);

exit;

?>