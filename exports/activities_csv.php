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
    $where[] = "DATE(activities.activity_date) >= :from_date";
    $params[":from_date"] = $from_date;
}

if (!empty($to_date)) {
    $where[] = "DATE(activities.activity_date) <= :to_date";
    $params[":to_date"] = $to_date;
}

$where_sql = "";

if (!empty($where)) {
    $where_sql = " WHERE " . implode(" AND ", $where);
}

$sql = "
    SELECT
        activities.id,
        activities.type,
        activities.subject,
        activities.description,
        activities.activity_date,
        users.name AS created_by_user,
        contacts.first_name,
        contacts.last_name,
        customers.customer_code
    FROM activities

    LEFT JOIN users
        ON activities.created_by = users.id

    LEFT JOIN contacts
        ON activities.contact_id = contacts.id

    LEFT JOIN customers
        ON activities.customer_id = customers.id

    $where_sql

    ORDER BY activities.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

$filename = "activities_report";

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
    "Type",
    "Subject",
    "Description",
    "Activity Date",
    "Created By",
    "Contact",
    "Customer Code"
]);

foreach ($activities as $activity) {

    $contact_name = "";

    if (!empty($activity["first_name"])) {
        $contact_name = trim(
            $activity["first_name"] . " " . ($activity["last_name"] ?? "")
        );
    }

    fputcsv($output, [
        $activity["id"],
        $activity["type"],
        $activity["subject"],
        $activity["description"] ?? "",
        $activity["activity_date"],
        $activity["created_by_user"] ?? "",
        $contact_name,
        $activity["customer_code"] ?? ""
    ]);
}

fclose($output);
exit;
?>