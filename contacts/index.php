<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

/* ==============================
   CONTACTS
============================== */

$stmt = $conn->query("
    SELECT
        c.id,
        c.first_name,
        c.last_name,
        c.email,
        c.phone,
        c.job_title,
        c.status,
        c.created_at,
        co.company_name
    FROM contacts c
    LEFT JOIN companies co
        ON co.id = c.company_id
    ORDER BY c.id DESC
");

$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==============================
   SUMMARY
============================== */

$total_contacts = count($contacts);

$active_contacts = 0;
$inactive_contacts = 0;
$total_with_email = 0;
$total_with_phone = 0;

foreach ($contacts as $contact) {

    $status = strtolower(
        trim($contact["status"] ?? "")
    );

    if (
        $status === "active" ||
        $status === "1"
    ) {
        $active_contacts++;
    } else {
        $inactive_contacts++;
    }

    if (!empty($contact["email"])) {
        $total_with_email++;
    }

    if (!empty($contact["phone"])) {
        $total_with_phone++;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Contacts - CRM</title>

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

            grid-template-columns:
                repeat(4, 1fr);

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

            padding:
                0 14px 0 37px;

            border:
                1px solid #dbe3ee;

            border-radius: 9px;

            outline: none;

            background: #ffffff;

            font-size: 13px;
        }

        .search-wrapper input:focus {
            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(
                    37,
                    99,
                    235,
                    0.10
                );
        }

        .filter-select {
            height: 40px;

            padding:
                0 12px;

            border:
                1px solid #dbe3ee;

            border-radius: 9px;

            background: #ffffff;

            color: #475569;

            outline: none;

            font-size: 13px;

            cursor: pointer;
        }

        .export-btn {
            display: inline-flex;

            align-items: center;

            gap: 7px;

            padding: 10px 15px;

            border:
                1px solid #dbe3ee;

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

            border:
                1px solid #e2e8f0;

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

        .contacts-table {
            width: 100%;

            border-collapse: collapse;
        }

        .contacts-table th {
            padding: 15px 16px;

            text-align: left;

            background: #f8fafc;

            border-bottom:
                1px solid #e2e8f0;

            color: #475569;

            font-size: 11px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: 0.05em;

            white-space: nowrap;
        }

        .contacts-table td {
            padding: 16px;

            border-bottom:
                1px solid #eef2f7;

            font-size: 13px;

            color: #334155;

            vertical-align: middle;
        }

        .contacts-table tbody tr:hover {
            background: #f8fbff;
        }

        .contacts-table tbody tr:last-child td {
            border-bottom: 0;
        }


        /* =========================
           COLUMN SIZING
        ========================= */

        .sno-column {
            width: 6%;
        }

        .contact-column {
            width: 17%;
        }

        .company-column {
            width: 14%;
        }

        .email-column {
            width: 18%;
        }

        .phone-column {
            width: 11%;
        }

        .job-column {
            width: 12%;
        }

        .status-column {
            width: 9%;
        }

        .created-column {
            width: 9%;
        }

        .action-column {
            width: 12%;

            min-width: 145px;

            white-space: nowrap;
        }


        /* =========================
           CONTACT DATA
        ========================= */

        .serial {
            color: #64748b;

            font-weight: 700;
        }

        .contact-name {
            color: #0f172a;

            font-weight: 700;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }

        .contact-code {
            margin-top: 4px;

            color: #94a3b8;

            font-size: 11px;

            white-space: nowrap;
        }

        .secondary {
            color: #64748b;
        }

        .data-text {
            display: block;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        /* =========================
           STATUS BADGES
        ========================= */

        .badge {
            display: inline-flex;

            align-items: center;

            padding: 5px 9px;

            border-radius: 999px;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;
        }

        .badge-active {
            background: #dcfce7;

            color: #15803d;
        }

        .badge-inactive {
            background: #fee2e2;

            color: #b91c1c;
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

            border-top:
                1px solid #eef2f7;

            color: #94a3b8;

            font-size: 12px;
        }


        /* =========================
           EMPTY STATE
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

            .contacts-table th,
            .contacts-table td {
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


    <!-- =========================
         PAGE HEADER
    ========================= -->

    <div class="page-header">

        <div class="title-area">

            <div class="title-icon">
                📇
            </div>

            <div>

                <h1>
                    Contacts
                </h1>

                <p>
                    Manage your contacts and business relationships
                </p>

            </div>

        </div>


        <a
            href="add.php"
            class="add-btn"
        >
            + Add Contact
        </a>

    </div>


    <!-- =========================
         SUMMARY
    ========================= -->

    <div class="summary-grid">


        <div class="summary-card">

            <div class="summary-label">
                Total Contacts
            </div>

            <div class="summary-value">

                <?php
                echo number_format(
                    $total_contacts
                );
                ?>

            </div>

            <div class="summary-small">
                All contact records
            </div>

        </div>


        <div class="summary-card">

            <div class="summary-label">
                Active
            </div>

            <div class="summary-value">

                <?php
                echo number_format(
                    $active_contacts
                );
                ?>

            </div>

            <div class="summary-small">
                Active contacts
            </div>

        </div>


        <div class="summary-card">

            <div class="summary-label">
                Inactive
            </div>

            <div class="summary-value">

                <?php
                echo number_format(
                    $inactive_contacts
                );
                ?>

            </div>

            <div class="summary-small">
                Inactive contacts
            </div>

        </div>


        <div class="summary-card">

            <div class="summary-label">
                With Email
            </div>

            <div class="summary-value">

                <?php
                echo number_format(
                    $total_with_email
                );
                ?>

            </div>

            <div class="summary-small">
                Contacts with email
            </div>

        </div>


    </div>


    <!-- =========================
         TOOLBAR
    ========================= -->

    <div class="toolbar">


        <div class="toolbar-left">


            <div class="search-wrapper">

                <span>
                    🔎
                </span>

                <input
                    type="text"
                    id="contactSearch"
                    placeholder="Search contacts..."
                    autocomplete="off"
                >

            </div>


            <select
                id="statusFilter"
                class="filter-select"
            >

                <option value="">
                    All Status
                </option>

                <option value="active">
                    Active
                </option>

                <option value="inactive">
                    Inactive
                </option>

            </select>


        </div>


        <a
            href="../exports/contacts_csv.php"
            class="export-btn"
        >
            ↓ Export CSV
        </a>


    </div>


    <!-- =========================
         TABLE
    ========================= -->

    <div class="table-card">

        <div class="table-wrap">


            <table
                class="contacts-table"
                id="contactsTable"
            >

                <thead>

                    <tr>

                        <th class="sno-column">
                            S.No
                        </th>

                        <th class="contact-column">
                            Contact
                        </th>

                        <th class="company-column">
                            Company
                        </th>

                        <th class="email-column">
                            Email
                        </th>

                        <th class="phone-column">
                            Phone
                        </th>

                        <th class="job-column">
                            Job Title
                        </th>

                        <th class="status-column">
                            Status
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
                    count($contacts) > 0
                ): ?>


                    <?php foreach (
                        $contacts
                        as $index => $contact
                    ): ?>


                        <?php

                        $status =
                            strtolower(
                                trim(
                                    $contact["status"] ?? ""
                                )
                            );

                        $is_active =
                            (
                                $status === "active" ||
                                $status === "1"
                            );

                        ?>


                        <tr
                            data-status="<?php
                                echo $is_active
                                    ? "active"
                                    : "inactive";
                            ?>"
                        >


                            <!-- S.NO -->

                            <td class="serial">

                                <?php
                                echo $index + 1;
                                ?>

                            </td>


                            <!-- CONTACT -->

                            <td>

                                <?php

                                $full_name =
                                    trim(
                                        (
                                            $contact[
                                                "first_name"
                                            ] ?? ""
                                        )
                                        .
                                        " "
                                        .
                                        (
                                            $contact[
                                                "last_name"
                                            ] ?? ""
                                        )
                                    );

                                ?>


                                <div class="contact-name">

                                    <?php

                                    echo htmlspecialchars(
                                        $full_name !== ""
                                            ? $full_name
                                            : "—"
                                    );

                                    ?>

                                </div>


                                <div class="contact-code">

                                    Contact ID:
                                    <?php
                                    echo (int)
                                        $contact["id"];
                                    ?>

                                </div>

                            </td>


                            <!-- COMPANY -->

                            <td class="secondary">

                                <?php if (
                                    !empty(
                                        $contact[
                                            "company_name"
                                        ]
                                    )
                                ): ?>

                                    <span class="data-text">

                                        <?php

                                        echo htmlspecialchars(
                                            $contact[
                                                "company_name"
                                            ]
                                        );

                                        ?>

                                    </span>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>


                            <!-- EMAIL -->

                            <td>

                                <?php if (
                                    !empty(
                                        $contact["email"]
                                    )
                                ): ?>

                                    <span class="data-text">

                                        <?php

                                        echo htmlspecialchars(
                                            $contact["email"]
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
                                        $contact["phone"]
                                    )
                                ): ?>

                                    <span class="data-text">

                                        <?php

                                        echo htmlspecialchars(
                                            $contact["phone"]
                                        );

                                        ?>

                                    </span>

                                <?php else: ?>

                                    <span class="secondary">
                                        —
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- JOB TITLE -->

                            <td class="secondary">

                                <?php if (
                                    !empty(
                                        $contact["job_title"]
                                    )
                                ): ?>

                                    <span class="data-text">

                                        <?php

                                        echo htmlspecialchars(
                                            $contact[
                                                "job_title"
                                            ]
                                        );

                                        ?>

                                    </span>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <span
                                    class="
                                        badge
                                        <?php
                                        echo $is_active
                                            ? "badge-active"
                                            : "badge-inactive";
                                        ?>
                                    "
                                >

                                    <?php

                                    echo $is_active
                                        ? "Active"
                                        : "Inactive";

                                    ?>

                                </span>

                            </td>


                            <!-- CREATED -->

                            <td class="secondary">

                                <?php

                                echo !empty(
                                    $contact[
                                        "created_at"
                                    ]
                                )
                                    ? date(
                                        "d M Y",
                                        strtotime(
                                            $contact[
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
                                            $contact["id"];
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
                                            $contact["id"];
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
                                            $contact["id"];
                                        ?>"
                                        class="delete-link"
                                        onclick="
                                            return confirm(
                                                'Are you sure you want to delete this contact?'
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
                                    📇
                                </div>

                                <h3>
                                    No contacts found
                                </h3>

                                <p>
                                    Add your first contact
                                    to get started.
                                </p>

                            </div>

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>


        </div>


        <!-- =========================
             FOOTER
        ========================= -->

        <div class="table-footer">

            <span>

                Showing

                <strong>
                    <?php
                    echo count($contacts);
                    ?>
                </strong>

                contacts

            </span>


            <span>
                CRM Contact Management
            </span>

        </div>


    </div>


</div>


<script>

const contactSearch =
    document.getElementById(
        "contactSearch"
    );

const statusFilter =
    document.getElementById(
        "statusFilter"
    );

const contactRows =
    document.querySelectorAll(
        "#contactsTable tbody tr[data-status]"
    );


function filterContacts() {

    const searchValue =
        contactSearch.value
            .toLowerCase()
            .trim();

    const statusValue =
        statusFilter.value;


    contactRows.forEach(
        function (row) {

            const rowText =
                row.textContent
                    .toLowerCase();

            const rowStatus =
                row.dataset.status;


            const matchesSearch =
                rowText.includes(
                    searchValue
                );


            const matchesStatus =
                statusValue === ""
                ||
                rowStatus === statusValue;


            row.style.display =
                matchesSearch &&
                matchesStatus
                    ? ""
                    : "none";

        }
    );
}


contactSearch.addEventListener(
    "input",
    filterContacts
);


statusFilter.addEventListener(
    "change",
    filterContacts
);

</script>


</body>

</html>