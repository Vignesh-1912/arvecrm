<?php

$page_title = $page_title ?? "Report";

$report_icons = [
    "Sales Report" => "💰",
    "Deals Report" => "💼",
    "Leads Report" => "🎯",
    "Customers Report" => "👥",
    "Quotes Report" => "📝",
    "Tasks & Activities Report" => "✅",
    "Companies Report" => "🏢",
    "Contacts Report" => "📇",
    "Products Report" => "📦"
];

$report_icon = $report_icons[$page_title] ?? "📊";

?>

<div class="header">

    <h1>
        <?php echo $report_icon; ?>
        <?php echo htmlspecialchars($page_title); ?>
    </h1>

    <a href="index.php" class="back-btn">
        ← Reports
    </a>

</div>
