<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$from_date = $_GET["from_date"] ?? "";
$to_date = $_GET["to_date"] ?? "";

$sql = "
    SELECT
        contacts.id,
        contacts.first_name,
        contacts.last_name,
        companies.company_name,
        contacts.email,
        contacts.phone,
        contacts.job_title,
        contacts.city,
        contacts.state,
        contacts.country,
        contacts.status,
        contacts.created_at
    FROM contacts
    LEFT JOIN companies
        ON contacts.company_id = companies.id
    WHERE 1=1
";

$params = [];

if (!empty($from_date)) {
    $sql .= " AND DATE(contacts.created_at) >= :from_date";
    $params[":from_date"] = $from_date;
}

if (!empty($to_date)) {
    $sql .= " AND DATE(contacts.created_at) <= :to_date";
    $params[":to_date"] = $to_date;
}

$sql .= " ORDER BY contacts.id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=contacts_report.csv");

$output = fopen("php://output", "w");

fputcsv($output, [
    "ID",
    "First Name",
    "Last Name",
    "Company",
    "Email",
    "Phone",
    "Job Title",
    "City",
    "State",
    "Country",
    "Status",
    "Created Date"
]);

foreach ($contacts as $contact) {
    fputcsv($output, [
        $contact["id"],
        $contact["first_name"],
        $contact["last_name"],
        $contact["company_name"] ?? "",
        $contact["email"] ?? "",
        $contact["phone"] ?? "",
        $contact["job_title"] ?? "",
        $contact["city"] ?? "",
        $contact["state"] ?? "",
        $contact["country"] ?? "",
        $contact["status"] ?? "",
        date("d-m-Y", strtotime($contact["created_at"]))
    ]);
}

fclose($output);
exit;

?>
