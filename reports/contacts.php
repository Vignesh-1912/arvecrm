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
    $where[] = "DATE(contacts.created_at) >= :from_date";
    $params[":from_date"] = $from_date;
}

if (!empty($to_date)) {
    $where[] = "DATE(contacts.created_at) <= :to_date";
    $params[":to_date"] = $to_date;
}

$where_sql = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";

$sql = "
    SELECT
        contacts.id,
        contacts.first_name,
        contacts.last_name,
        companies.company_name,
        contacts.email,
        contacts.phone,
        contacts.job_title,
        contacts.status,
        contacts.created_at
    FROM contacts
    LEFT JOIN companies
        ON contacts.company_id = companies.id
    $where_sql
    ORDER BY contacts.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$active_contacts = 0;
$statuses = [];

foreach ($contacts as $contact) {
    $status = trim($contact["status"] ?? "");

    if (strtolower($status) === "active") {
        $active_contacts++;
    }

    if ($status !== "") {
        $statuses[$status] = true;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacts Report</title>
    <link rel="stylesheet" href="/crm/assets/css/sidebar.css">
    <style>
        .page-header {
            margin-bottom: 25px;
        }

        .page-header h1 {
            margin: 0 0 5px;
        }

        .page-header p {
            color: #6b7280;
        }

        .filter-box,
        .summary-card,
        .table-box {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .filter-box {
            padding: 20px;
            margin-bottom: 25px;
        }

        .filter-form {
            display: flex;
            align-items: end;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .filter-group label {
            font-weight: bold;
            color: #374151;
        }

        .filter-group input {
            height: 42px;
            padding: 8px 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 42px;
            padding: 8px 16px;
            border: 0;
            border-radius: 6px;
            color: white;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-primary {
            background: #2563eb;
        }

        .btn-secondary {
            background: #6b7280;
        }

        .btn-success {
            background: #16a34a;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .summary-card {
            padding: 22px;
        }

        .summary-card h3 {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }

        .summary-card .value {
            margin-top: 10px;
            font-size: 28px;
            font-weight: bold;
            color: #111827;
        }

        .table-box {
            padding: 20px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 900px;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        th {
            background: #f3f4f6;
            white-space: nowrap;
        }

        tr:hover {
            background: #f9fafb;
        }

        .no-data {
            padding: 25px;
            text-align: center;
            color: #6b7280;
        }

        @media (max-width: 800px) {
            .summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">
    <div class="page-header">
        <h1>Contacts Report</h1>
        <p>Contact directory and status overview</p>
    </div>

    <div class="filter-box">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label for="from_date">From Date</label>
                <input
                    type="date"
                    id="from_date"
                    name="from_date"
                    value="<?php echo htmlspecialchars($from_date); ?>"
                >
            </div>

            <div class="filter-group">
                <label for="to_date">To Date</label>
                <input
                    type="date"
                    id="to_date"
                    name="to_date"
                    value="<?php echo htmlspecialchars($to_date); ?>"
                >
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Apply Filter</button>
                <a href="contacts.php" class="btn btn-secondary">Clear</a>
                <a
                    href="../exports/contacts_csv.php?from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>"
                    class="btn btn-success"
                >
                    Export CSV
                </a>
            </div>
        </form>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <h3>Total Contacts</h3>
            <div class="value"><?php echo count($contacts); ?></div>
        </div>

        <div class="summary-card">
            <h3>Active Contacts</h3>
            <div class="value"><?php echo $active_contacts; ?></div>
        </div>

        <div class="summary-card">
            <h3>Statuses</h3>
            <div class="value"><?php echo count($statuses); ?></div>
        </div>
    </div>

    <div class="table-box">
        <h2>Contact Details</h2>

        <?php if (!empty($contacts)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Company</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Job Title</th>
                        <th>Status</th>
                        <th>Created Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contacts as $contact): ?>
                        <tr>
                            <td><?php echo (int) $contact["id"]; ?></td>
                            <td>
                                <?php
                                echo htmlspecialchars(
                                    trim(
                                        ($contact["first_name"] ?? "") . " " .
                                        ($contact["last_name"] ?? "")
                                    )
                                );
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($contact["company_name"] ?? ""); ?></td>
                            <td><?php echo htmlspecialchars($contact["email"] ?? ""); ?></td>
                            <td><?php echo htmlspecialchars($contact["phone"] ?? ""); ?></td>
                            <td><?php echo htmlspecialchars($contact["job_title"] ?? ""); ?></td>
                            <td><?php echo htmlspecialchars($contact["status"] ?? ""); ?></td>
                            <td><?php echo htmlspecialchars($contact["created_at"] ?? ""); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">No contacts found.</div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
