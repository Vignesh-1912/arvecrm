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
        id,
        company_name,
        industry,
        phone,
        email,
        website,
        city,
        state,
        country,
        created_at
    FROM companies
    WHERE 1=1
";

$params = [];

if (!empty($from_date)) {
    $sql .= " AND DATE(created_at) >= :from_date";
    $params[":from_date"] = $from_date;
}

if (!empty($to_date)) {
    $sql .= " AND DATE(created_at) <= :to_date";
    $params[":to_date"] = $to_date;
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=companies_report.csv");

$output = fopen("php://output", "w");

fputcsv($output, [
    "ID",
    "Company Name",
    "Industry",
    "Phone",
    "Email",
    "Website",
    "City",
    "State",
    "Country",
    "Created Date"
]);

foreach ($companies as $company) {
    fputcsv($output, [
        $company["id"],
        $company["company_name"],
        $company["industry"],
        $company["phone"],
        $company["email"],
        $company["website"],
        $company["city"],
        $company["state"],
        $company["country"],
        date("d-m-Y", strtotime($company["created_at"]))
    ]);
}

fclose($output);
exit;

?>
