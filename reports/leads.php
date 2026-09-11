<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}


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
        leads.source,
        leads.status,
        leads.lead_value,
        leads.created_at,
        companies.company_name,
        contacts.first_name,
        contacts.last_name,
        users.name AS assigned_name
    FROM leads

    LEFT JOIN companies
        ON leads.company_id = companies.id

    LEFT JOIN contacts
        ON leads.contact_id = contacts.id

    LEFT JOIN users
        ON leads.assigned_to = users.id

    $where_sql

    ORDER BY leads.created_at DESC, leads.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================
   SUMMARY
========================= */

$total_leads = count($leads);

$total_value = 0;
$new_leads = 0;
$qualified_leads = 0;
$converted_leads = 0;

foreach ($leads as $lead) {

    $total_value += (float) $lead["lead_value"];

    $status = strtolower($lead["status"]);

    if ($status == "new") {
        $new_leads++;
    }

    if ($status == "qualified") {
        $qualified_leads++;
    }

    if ($status == "converted") {
        $converted_leads++;
    }
}

?>

<!DOCTYPE html>
<html>

<head>

    <title>Leads Report</title>

    <link rel="stylesheet" href="/crm/assets/css/sidebar.css">

    <style>
        /* Filter button area */
.filter-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

/* Common button style */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 20px;
    height: 48px;
    border-radius: 6px;
    font-size: 16px;
    font-family: Arial, sans-serif;
    text-decoration: none;
    border: none;
    cursor: pointer;
    box-sizing: border-box;
}

/* Apply Filter */
.btn-primary {
    background: #2563eb;
    color: white !important;
}

.btn-primary:hover {
    background: #1d4ed8;
}

/* Clear */
.btn-secondary {
    background: #6b7280;
    color: white !important;
}

.btn-secondary:hover {
    background: #4b5563;
}

/* Export buttons */
.btn-success {
    background: #16a34a !important;
    color: white !important;
    text-decoration: none !important;
    border: none !important;
}

.btn-success:hover {
    background: #15803d !important;
    color: white !important;
    text-decoration: none !important;
}

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .report-header h1 {
            margin: 0;
        }

        .report-header p {
            color: #6b7280;
            margin-top: 5px;
        }

        .back-button {
            background: #6b7280;
            color: white;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 6px;
        }

        .filter-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .filter-box h3 {
            margin-top: 0;
        }

        .filter-form {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .filter-group label {
            font-size: 13px;
            font-weight: bold;
        }

        .filter-group input {
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
        }

        .filter-button {
            background: #2563eb;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
        }

        .filter-button:hover {
            background: #1d4ed8;
        }

        .clear-button {
            background: #6b7280;
            color: white;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 6px;
        }

        .btn-success {
            background: #16a34a;
            color: white;
        }

        .btn-success:hover {
            background: #15803d;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: white;
            padding: 22px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .summary-card h3 {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }

        .summary-card .value {
            margin-top: 10px;
            font-size: 25px;
            font-weight: bold;
        }

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 13px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        th {
            background: #f3f4f6;
            font-size: 14px;
        }

        td {
            font-size: 14px;
        }

        .status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 5px;
            font-size: 12px;
            background: #dbeafe;
            color: #1e40af;
        }

        .empty {
            text-align: center;
            padding: 30px;
            color: #6b7280;
        }

        @media (max-width: 1000px) {

            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }

        }

        @media (max-width: 700px) {

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .report-header {
                display: block;
            }

            .back-button {
                display: inline-block;
                margin-top: 15px;
            }

        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <div class="report-header">

        <div>

            <h1>🎯 Leads Report</h1>

            <p>
                Lead pipeline, sources and value overview
            </p>

        </div>

        <a href="index.php" class="back-button">
            ← Back to Reports
        </a>

    </div>


    <!-- DATE FILTER -->

    <div class="filter-box">

        <h3>📅 Filter Leads by Created Date</h3>

        <form method="GET" class="filter-form">

            <div class="filter-group">

                <label>From Date</label>

                <input
                    type="date"
                    name="from_date"
                    value="<?php echo htmlspecialchars($from_date); ?>"
                >

            </div>


            <div class="filter-group">

                <label>To Date</label>

                <input
                    type="date"
                    name="to_date"
                    value="<?php echo htmlspecialchars($to_date); ?>"
                >

            </div>


            <button type="submit" class="filter-button">
                Apply Filter
            </button>


            <a href="leads.php" class="clear-button">
                Clear
            </a>

            <a
                href="../exports/leads_csv.php?from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>"
                class="btn btn-success"
            >
                Export CSV
            </a>

            <a
                href="../exports/leads_excel.php?from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>"
                class="btn btn-success"
            >
                Export Excel
            </a>

            <a
                href="../exports/leads_pdf.php?from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>"
                class="btn btn-success"
            >
                Export PDF
            </a>

        </form>

    </div>


    <!-- SUMMARY -->

    <div class="summary-grid">

        <div class="summary-card">

            <h3>Total Leads</h3>

            <div class="value">
                <?php echo $total_leads; ?>
            </div>

        </div>


        <div class="summary-card">

            <h3>Total Lead Value</h3>

            <div class="value">
                ₹<?php echo number_format($total_value, 2); ?>
            </div>

        </div>


        <div class="summary-card">

            <h3>New Leads</h3>

            <div class="value">
                <?php echo $new_leads; ?>
            </div>

        </div>


        <div class="summary-card">

            <h3>Qualified Leads</h3>

            <div class="value">
                <?php echo $qualified_leads; ?>
            </div>

        </div>


        <div class="summary-card">

            <h3>Converted Leads</h3>

            <div class="value">
                <?php echo $converted_leads; ?>
            </div>

        </div>

    </div>


    <!-- LEADS TABLE -->

    <div class="table-container">

        <h2>Lead Details</h2>

        <table>

            <thead>

                <tr>

                    <th>ID</th>
                    <th>Lead Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Company</th>
                    <th>Contact</th>
                    <th>Source</th>
                    <th>Status</th>
                    <th>Lead Value</th>
                    <th>Created Date</th>
                    <th>Assigned To</th>

                </tr>

            </thead>

            <tbody>

                <?php if (empty($leads)): ?>

                    <tr>

                        <td colspan="11" class="empty">
                            No leads found for the selected date range.
                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach ($leads as $lead): ?>

                        <tr>

                            <td>
                                <?php echo $lead["id"]; ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $lead["lead_name"]
                                );
                                ?>
                            </td>

                            <td>

                                <?php

                                echo !empty($lead["email"])
                                    ? htmlspecialchars($lead["email"])
                                    : "-";

                                ?>

                            </td>

                            <td>

                                <?php

                                echo !empty($lead["phone"])
                                    ? htmlspecialchars($lead["phone"])
                                    : "-";

                                ?>

                            </td>

                            <td>

                                <?php

                                echo !empty($lead["company_name"])
                                    ? htmlspecialchars(
                                        $lead["company_name"]
                                    )
                                    : "-";

                                ?>

                            </td>

                            <td>

                                <?php

                                if (
                                    !empty($lead["first_name"]) ||
                                    !empty($lead["last_name"])
                                ) {

                                    echo htmlspecialchars(
                                        trim(
                                            $lead["first_name"] . " " .
                                            $lead["last_name"]
                                        )
                                    );

                                } else {

                                    echo "-";

                                }

                                ?>

                            </td>

                            <td>

                                <?php

                                echo !empty($lead["source"])
                                    ? htmlspecialchars($lead["source"])
                                    : "-";

                                ?>

                            </td>

                            <td>

                                <span class="status">

                                    <?php

                                    echo htmlspecialchars(
                                        ucfirst(
                                            $lead["status"]
                                        )
                                    );

                                    ?>

                                </span>

                            </td>

                            <td>

                                ₹<?php

                                echo number_format(
                                    $lead["lead_value"],
                                    2
                                );

                                ?>

                            </td>

                            <td>

                                <?php

                                echo !empty($lead["created_at"])
                                    ? date(
                                        "d-m-Y",
                                        strtotime(
                                            $lead["created_at"]
                                        )
                                    )
                                    : "-";

                                ?>

                            </td>

                            <td>

                                <?php

                                echo !empty(
                                    $lead["assigned_name"]
                                )
                                    ? htmlspecialchars(
                                        $lead["assigned_name"]
                                    )
                                    : "-";

                                ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>

</html>