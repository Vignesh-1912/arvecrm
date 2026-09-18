<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

/* ==============================
   COMPANIES
============================== */

$stmt = $conn->query("
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
        postal_code,
        created_at
    FROM companies
    ORDER BY id DESC
");

$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==============================
   SUMMARY
============================== */

$total_companies = count($companies);

$stmt = $conn->query("
    SELECT COUNT(DISTINCT industry)
    FROM companies
    WHERE industry IS NOT NULL
      AND TRIM(industry) <> ''
");

$total_industries = (int) $stmt->fetchColumn();

$stmt = $conn->query("
    SELECT COUNT(*)
    FROM companies
    WHERE email IS NOT NULL
      AND TRIM(email) <> ''
");

$total_email = (int) $stmt->fetchColumn();

$stmt = $conn->query("
    SELECT COUNT(*)
    FROM companies
    WHERE website IS NOT NULL
      AND TRIM(website) <> ''
");

$total_website = (int) $stmt->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Companies - CRM</title>

    <link
        rel="stylesheet"
        href="/crm/assets/css/sidebar.css"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f8fafc;
            font-family: Arial, sans-serif;
            color: #0f172a;
            overflow-x: hidden;
        }

        .main-content {
            margin-left: 250px;
            min-height: 100vh;
            padding: 28px;
            overflow-x: hidden;
        }


        /* =========================
           HEADER
        ========================= */

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px 24px;
            margin-bottom: 20px;
            box-shadow:
                0 2px 8px
                rgba(15, 23, 42, 0.04);
        }

        .title-area {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .title-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #dbeafe;
            font-size: 23px;
        }

        .page-header h1 {
            margin: 0;
            font-size: 24px;
            color: #0f172a;
        }

        .page-header p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 13px;
        }

        .add-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 11px 17px;
            background: #2563eb;
            color: #ffffff;
            text-decoration: none;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 700;
            transition: 0.2s;
            white-space: nowrap;
        }

        .add-btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }


        /* =========================
           SUMMARY CARDS
        ========================= */

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }

        .summary-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px;
            box-shadow:
                0 2px 8px
                rgba(15, 23, 42, 0.03);
        }

        .summary-label {
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .summary-value {
            margin-top: 8px;
            font-size: 26px;
            font-weight: 700;
            color: #0f172a;
        }

        .summary-small {
            margin-top: 5px;
            color: #94a3b8;
            font-size: 11px;
        }


        /* =========================
           TOOLBAR
        ========================= */

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .toolbar-left {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .search-wrapper {
            position: relative;
        }

        .search-wrapper span {
            position: absolute;
            left: 13px;
            top: 11px;
            color: #94a3b8;
        }

        .search-wrapper input {
            width: 280px;
            height: 40px;
            padding: 0 14px 0 37px;
            border: 1px solid #dbe3ee;
            border-radius: 9px;
            outline: none;
            background: #ffffff;
            font-size: 13px;
        }

        .search-wrapper input:focus {
            border-color: #2563eb;
            box-shadow:
                0 0 0 3px
                rgba(37, 99, 235, 0.10);
        }

        .export-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 15px;
            border: 1px solid #dbe3ee;
            border-radius: 9px;
            background: #ffffff;
            color: #334155;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
        }

        .export-btn:hover {
            background: #f8fafc;
        }


        /* =========================
           TABLE
        ========================= */

        .table-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
            box-shadow:
                0 2px 8px
                rgba(15, 23, 42, 0.04);
        }

        .table-wrap {
            width: 100%;
            overflow: hidden;
        }

        .companies-table {
            width: 100%;
            border-collapse: collapse;
        }

        .companies-table th {
            padding: 15px 16px;
            text-align: left;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            color: #475569;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }

        .companies-table td {
            padding: 16px;
            border-bottom: 1px solid #eef2f7;
            font-size: 13px;
            color: #334155;
            vertical-align: middle;
        }

        .companies-table tbody tr:hover {
            background: #f8fbff;
        }

        .companies-table tbody tr:last-child td {
            border-bottom: 0;
        }


        /* =========================
           COLUMN SIZING
        ========================= */

        .sno-column {
            width: 6%;
        }

        .company-column {
            width: 15%;
        }

        .industry-column {
            width: 10%;
        }

        .phone-column {
            width: 11%;
        }

        .email-column {
            width: 17%;
        }

        .website-column {
            width: 8%;
        }

        .location-column {
            width: 14%;
        }

        .created-column {
            width: 9%;
        }

        .action-column {
            width: 10%;
            min-width: 145px;
            white-space: nowrap;
        }


        /* =========================
           COMPANY DATA
        ========================= */

        .serial {
            color: #64748b;
            font-weight: 700;
        }

        .company-name {
            color: #0f172a;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .company-code {
            margin-top: 4px;
            color: #94a3b8;
            font-size: 11px;
            white-space: nowrap;
        }

        .secondary {
            color: #64748b;
        }

        .company-data {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
        }


        /* =========================
           INDUSTRY BADGE
        ========================= */

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            white-space: nowrap;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .badge-industry {
            background: #dbeafe;
            color: #1d4ed8;
        }


        /* =========================
           ACTIONS
        ========================= */

        .action-links {
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .action-links a {
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
        }

        .action-links a:hover {
            text-decoration: underline;
        }

        .view-link {
            color: #2563eb;
        }

        .edit-link {
            color: #059669;
        }

        .delete-link {
            color: #dc2626;
        }

        .action-links span {
            color: #cbd5e1;
        }


        /* =========================
           FOOTER
        ========================= */

        .table-footer {
            display: flex;
            justify-content: space-between;
            padding: 14px 16px;
            border-top: 1px solid #eef2f7;
            color: #94a3b8;
            font-size: 12px;
        }


        /* =========================
           EMPTY
        ========================= */

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-icon {
            font-size: 42px;
            margin-bottom: 10px;
        }

        .empty-state h3 {
            margin: 0;
            color: #334155;
        }

        .empty-state p {
            margin: 8px 0 0;
            color: #94a3b8;
            font-size: 13px;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1200px) {

            .summary-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }

            .companies-table th,
            .companies-table td {
                padding:
                    12px 10px;
            }
        }

        @media (max-width: 768px) {

            .main-content {
                margin-left: 220px;
                padding: 18px;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .search-wrapper input {
                width: 100%;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }
        }

    </style>

</head>


<body>


<?php include "../includes/sidebar.php"; ?>


<div class="main-content">


    <!-- PAGE HEADER -->

    <div class="page-header">

        <div class="title-area">

            <div class="title-icon">
                🏢
            </div>

            <div>

                <h1>
                    Companies
                </h1>

                <p>
                    Manage your companies and business relationships
                </p>

            </div>

        </div>


        <a
            href="add.php"
            class="add-btn"
        >
            + Add Company
        </a>

    </div>


    <!-- SUMMARY -->

    <div class="summary-grid">


        <div class="summary-card">

            <div class="summary-label">
                Total Companies
            </div>

            <div class="summary-value">

                <?php
                echo number_format(
                    $total_companies
                );
                ?>

            </div>

            <div class="summary-small">
                All company records
            </div>

        </div>


        <div class="summary-card">

            <div class="summary-label">
                Industries
            </div>

            <div class="summary-value">

                <?php
                echo number_format(
                    $total_industries
                );
                ?>

            </div>

            <div class="summary-small">
                Different industries
            </div>

        </div>


        <div class="summary-card">

            <div class="summary-label">
                With Email
            </div>

            <div class="summary-value">

                <?php
                echo number_format(
                    $total_email
                );
                ?>

            </div>

            <div class="summary-small">
                Companies with email
            </div>

        </div>


        <div class="summary-card">

            <div class="summary-label">
                With Website
            </div>

            <div class="summary-value">

                <?php
                echo number_format(
                    $total_website
                );
                ?>

            </div>

            <div class="summary-small">
                Companies with website
            </div>

        </div>

    </div>


    <!-- TOOLBAR -->

    <div class="toolbar">

        <div class="toolbar-left">

            <div class="search-wrapper">

                <span>
                    🔎
                </span>

                <input
                    type="text"
                    id="companySearch"
                    placeholder="Search companies..."
                    autocomplete="off"
                >

            </div>

        </div>


        <a
            href="../exports/companies_csv.php"
            class="export-btn"
        >
            ↓ Export CSV
        </a>

    </div>


    <!-- TABLE -->

    <div class="table-card">

        <div class="table-wrap">

            <table
                class="companies-table"
                id="companiesTable"
            >

                <thead>

                    <tr>

                        <th class="sno-column">
                            S.No
                        </th>

                        <th class="company-column">
                            Company
                        </th>

                        <th class="industry-column">
                            Industry
                        </th>

                        <th class="phone-column">
                            Phone
                        </th>

                        <th class="email-column">
                            Email
                        </th>

                        <th class="website-column">
                            Website
                        </th>

                        <th class="location-column">
                            Location
                        </th>

                        <th class="created-column">
                            Created
                        </th>

                        <th class="action-column">
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if (
                    count($companies) > 0
                ): ?>


                    <?php foreach (
                        $companies
                        as $index => $company
                    ): ?>


                        <tr>


                            <!-- S.NO -->

                            <td class="serial">

                                <?php
                                echo $index + 1;
                                ?>

                            </td>


                            <!-- COMPANY -->

                            <td>

                                <div class="company-name">

                                    <?php

                                    echo htmlspecialchars(
                                        $company[
                                            "company_name"
                                        ] ?? ""
                                    );

                                    ?>

                                </div>

                                <div class="company-code">

                                    Company ID:
                                    <?php
                                    echo (int)
                                        $company["id"];
                                    ?>

                                </div>

                            </td>


                            <!-- INDUSTRY -->

                            <td>

                                <?php if (
                                    !empty(
                                        $company["industry"]
                                    )
                                ): ?>

                                    <span
                                        class="badge badge-industry"
                                    >

                                        <?php

                                        echo htmlspecialchars(
                                            $company[
                                                "industry"
                                            ]
                                        );

                                        ?>

                                    </span>

                                <?php else: ?>

                                    <span class="secondary">
                                        —
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- PHONE -->

                            <td>

                                <?php if (
                                    !empty(
                                        $company["phone"]
                                    )
                                ): ?>

                                    <span class="company-data">

                                        <?php

                                        echo htmlspecialchars(
                                            $company["phone"]
                                        );

                                        ?>

                                    </span>

                                <?php else: ?>

                                    <span class="secondary">
                                        —
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- EMAIL -->

                            <td>

                                <?php if (
                                    !empty(
                                        $company["email"]
                                    )
                                ): ?>

                                    <span class="company-data">

                                        <?php

                                        echo htmlspecialchars(
                                            $company["email"]
                                        );

                                        ?>

                                    </span>

                                <?php else: ?>

                                    <span class="secondary">
                                        —
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- WEBSITE -->

                            <td>

                                <?php if (
                                    !empty(
                                        $company["website"]
                                    )
                                ): ?>

                                    <a
                                        href="<?php
                                        echo htmlspecialchars(
                                            $company["website"]
                                        );
                                        ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="view-link"
                                    >
                                        Visit
                                    </a>

                                <?php else: ?>

                                    <span class="secondary">
                                        —
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- LOCATION -->

                            <td>

                                <?php

                                $location_parts = [];

                                if (
                                    !empty(
                                        $company["city"]
                                    )
                                ) {
                                    $location_parts[] =
                                        $company["city"];
                                }

                                if (
                                    !empty(
                                        $company["state"]
                                    )
                                ) {
                                    $location_parts[] =
                                        $company["state"];
                                }

                                if (
                                    !empty(
                                        $company["country"]
                                    )
                                ) {
                                    $location_parts[] =
                                        $company["country"];
                                }

                                if (
                                    count(
                                        $location_parts
                                    ) > 0
                                ) {

                                    echo htmlspecialchars(
                                        implode(
                                            ", ",
                                            $location_parts
                                        )
                                    );

                                } else {

                                    echo '<span class="secondary">—</span>';

                                }

                                ?>

                            </td>


                            <!-- CREATED -->

                            <td class="secondary">

                                <?php

                                echo !empty(
                                    $company["created_at"]
                                )
                                    ? date(
                                        "d M Y",
                                        strtotime(
                                            $company[
                                                "created_at"
                                            ]
                                        )
                                    )
                                    : "—";

                                ?>

                            </td>


                            <!-- ACTION -->

                            <td class="action-column">

                                <div class="action-links">

                                    <a
                                        href="view.php?id=<?php
                                        echo (int)
                                            $company["id"];
                                        ?>"
                                        class="view-link"
                                    >
                                        View
                                    </a>

                                    <span>
                                        |
                                    </span>

                                    <a
                                        href="edit.php?id=<?php
                                        echo (int)
                                            $company["id"];
                                        ?>"
                                        class="edit-link"
                                    >
                                        Edit
                                    </a>

                                    <span>
                                        |
                                    </span>

                                    <a
                                        href="delete.php?id=<?php
                                        echo (int)
                                            $company["id"];
                                        ?>"
                                        class="delete-link"
                                        onclick="
                                            return confirm(
                                                'Are you sure you want to delete this company?'
                                            );
                                        "
                                    >
                                        Delete
                                    </a>

                                </div>

                            </td>

                        </tr>


                    <?php endforeach; ?>


                <?php else: ?>


                    <tr>

                        <td colspan="9">

                            <div class="empty-state">

                                <div class="empty-icon">
                                    🏢
                                </div>

                                <h3>
                                    No companies found
                                </h3>

                                <p>
                                    Add your first company
                                    to get started.
                                </p>

                            </div>

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>

        </div>


        <!-- FOOTER -->

        <div class="table-footer">

            <span>

                Showing

                <?php
                echo count($companies);
                ?>

                companies

            </span>


            <span>
                CRM Company Management
            </span>

        </div>

    </div>

</div>


<script>

const companySearch =
    document.getElementById(
        "companySearch"
    );

const companyRows =
    document.querySelectorAll(
        "#companiesTable tbody tr"
    );


companySearch.addEventListener(
    "input",
    function () {

        const searchValue =
            this.value
                .toLowerCase()
                .trim();


        companyRows.forEach(
            function (row) {

                const rowText =
                    row.textContent
                        .toLowerCase();


                row.style.display =
                    rowText.includes(
                        searchValue
                    )
                        ? ""
                        : "none";

            }
        );

    }
);

</script>


</body>

</html>