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
    $where[] = "DATE(companies.created_at) >= :from_date";
    $params[":from_date"] = $from_date;
}

if (!empty($to_date)) {
    $where[] = "DATE(companies.created_at) <= :to_date";
    $params[":to_date"] = $to_date;
}

$where_sql = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";

$sql = "
    SELECT
        companies.id,
        companies.company_name,
        companies.industry,
        companies.phone,
        companies.email,
        companies.website,
        companies.city,
        companies.state,
        companies.country,
        companies.created_at,
        users.name AS created_by
    FROM companies
    LEFT JOIN users
        ON companies.created_by = users.id
    $where_sql
    ORDER BY companies.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_companies = count($companies);
$industries = [];

foreach ($companies as $company) {
    $industry = trim($company["industry"] ?? "");

    if ($industry !== "") {
        $industries[$industry] = true;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Companies Report</title>
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
            grid-template-columns: repeat(2, 1fr);
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
            min-width: 950px;
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

        @media (max-width: 700px) {
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
        <h1>Companies Report</h1>
        <p>Company directory and registration overview</p>
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
                <a href="companies.php" class="btn btn-secondary">Clear</a>
                <a
                    href="../exports/companies_csv.php?from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>"
                    class="btn btn-success"
                >
                    Export CSV
                </a>
                <a
                    href="../exports/companies_excel.php?from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>"
                    class="btn btn-success"
                >
                    Export Excel
                </a>
                <a
                    href="../exports/companies_pdf.php?from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>"
                    class="btn btn-success"
                >
                    Export PDF
                </a>
            </div>
        </form>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <h3>Total Companies</h3>
            <div class="value"><?php echo $total_companies; ?></div>
        </div>

        <div class="summary-card">
            <h3>Industries Represented</h3>
            <div class="value"><?php echo count($industries); ?></div>
        </div>
    </div>

    <div class="table-box">
        <h2>Company Details</h2>

        <?php if (!empty($companies)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Company Name</th>
                        <th>Industry</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Website</th>
                        <th>Location</th>
                        <th>Created Date</th>
                        <th>Created By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($companies as $company): ?>
                        <tr>
                            <td><?php echo (int) $company["id"]; ?></td>
                            <td><?php echo htmlspecialchars($company["company_name"]); ?></td>
                            <td><?php echo htmlspecialchars($company["industry"] ?? ""); ?></td>
                            <td><?php echo htmlspecialchars($company["phone"] ?? ""); ?></td>
                            <td><?php echo htmlspecialchars($company["email"] ?? ""); ?></td>
                            <td><?php echo htmlspecialchars($company["website"] ?? ""); ?></td>
                            <td>
                                <?php
                                echo htmlspecialchars(
                                    trim(
                                        implode(
                                            ", ",
                                            array_filter([
                                                $company["city"] ?? "",
                                                $company["state"] ?? "",
                                                $company["country"] ?? ""
                                            ])
                                        )
                                    )
                                );
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($company["created_at"] ?? ""); ?></td>
                            <td><?php echo htmlspecialchars($company["created_by"] ?? ""); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">No companies found.</div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
