<?php

$current_page = basename($_SERVER["PHP_SELF"]);
$current_folder = basename(dirname($_SERVER["PHP_SELF"]));

?>

<div class="sidebar">

    <div class="sidebar-logo">

        <h2>CRM</h2>

        <p>Management System</p>

    </div>

    <div class="sidebar-menu">

        <a href="/crm/dashboard/index.php"
           class="<?php echo ($current_folder == 'dashboard') ? 'active' : ''; ?>">

            📊 Dashboard

        </a>

        <a href="/crm/customers/index.php"
           class="<?php echo ($current_folder == 'customers') ? 'active' : ''; ?>">

            👥 Customers

        </a>

        <a href="/crm/companies/index.php"
           class="<?php echo ($current_folder == 'companies') ? 'active' : ''; ?>">

            🏢 Companies

        </a>

        <a href="/crm/contacts/index.php"
           class="<?php echo ($current_folder == 'contacts') ? 'active' : ''; ?>">

            📇 Contacts

        </a>

        <a href="/crm/leads/index.php"
           class="<?php echo ($current_folder == 'leads') ? 'active' : ''; ?>">

            🎯 Leads

        </a>

        <a href="/crm/deals/index.php"
           class="<?php echo ($current_folder == 'deals') ? 'active' : ''; ?>">

            💼 Deals

        </a>

        <a href="/crm/products/index.php"
           class="<?php echo ($current_folder == 'products') ? 'active' : ''; ?>">

            📦 Products

        </a>

        <a href="/crm/quotes/index.php"
           class="<?php echo ($current_folder == 'quotes') ? 'active' : ''; ?>">

            📝 Quotes

        </a>

        <a href="/crm/sales/index.php"
           class="<?php echo ($current_folder == 'sales') ? 'active' : ''; ?>">

            💰 Sales

        </a>

        <a href="/crm/tasks/index.php"
           class="<?php echo ($current_folder == 'tasks') ? 'active' : ''; ?>">

            ✅ Tasks

        </a>

        <a href="/crm/activities/index.php"
           class="<?php echo ($current_folder == 'activities') ? 'active' : ''; ?>">

            📅 Activities

        </a>

        <a href="/crm/reports/index.php"
           class="<?php echo ($current_folder == 'reports') ? 'active' : ''; ?>">

            📈 Reports

        </a>

    </div>

    <div class="sidebar-bottom">

        <a href="/crm/auth/logout.php">

            🚪 Logout

        </a>

    </div>

</div>
