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
    $where[] = "DATE(leads.created_at) >= :from_date";
    $params[":from_date"] = $from_date;
}

if (!empty($to_date)) {
    $where[] = "DATE(leads.created_at) <= :to_date";
    $params[":to_date"] = $to_date;
}

$where_sql = "";

if (!empty($where)) {
    $where_sql = " WHERE " . implode(" AND ", $where);
}

/* =========================
   GET LEADS
========================= */

$sql = "
    SELECT
        leads.id,
        leads.lead_name,
        leads.email,
        leads.phone,
        companies.company_name,
        contacts.first_name,
        contacts.last_name,
        leads.source,
        leads.status,
        leads.lead_value,
        leads.created_at,
        users.name AS assigned_user,
        leads.notes
    FROM leads

    LEFT JOIN companies
        ON leads.company_id = companies.id

    LEFT JOIN contacts
        ON leads.contact_id = contacts.id

    LEFT JOIN users
        ON leads.assigned_to = users.id

    $where_sql

    ORDER BY leads.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   FILE NAME
========================= */

$filename = "leads_report";

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
   CSV HEADER ROW
========================= */

fputcsv($output, [
    "ID",
    "Lead Name",
    "Email",
    "Phone",
    "Company",
    "Contact",
    "Source",
    "Status",
    "Lead Value",
    "Created Date",
    "Assigned To",
    "Notes"
]);

/* =========================
   CSV DATA
========================= */

foreach ($leads as $lead) {

    $contact_name = "";

    if (!empty($lead["first_name"])) {

        $contact_name = trim(
            $lead["first_name"] .
            " " .
            ($lead["last_name"] ?? "")
        );
    }

    fputcsv($output, [

        $lead["id"],

        $lead["lead_name"],

        $lead["email"] ?? "",

        $lead["phone"] ?? "",

        $lead["company_name"] ?? "",

        $contact_name,

        $lead["source"] ?? "",

        $lead["status"] ?? "",

        $lead["lead_value"],

        $lead["created_at"],

        $lead["assigned_user"] ?? "",

        $lead["notes"] ?? ""

    ]);
}

fclose($output);

exit;

?>