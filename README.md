# ArveCRM

A PHP/MySQL CRM application for managing customers, companies and related sales workflows.

## Local setup

1. Install XAMPP with Apache, PHP and MySQL.
2. Clone this repository into `htdocs/arvecrm`.
3. Create a MySQL database named `crm_database`.
4. Configure `DB_HOST`, `DB_NAME`, `DB_USER` and `DB_PASSWORD` as environment variables when possible. The development defaults remain compatible with XAMPP (`localhost`, `crm_database`, `root`, empty password).
5. Import your CRM database schema into `crm_database`.
6. Open `/arvecrm/auth/login.php` in the browser.

## Security baseline

- PDO uses exceptions, native prepared statements and associative fetches.
- Authentication regenerates the session ID after successful login.
- Session cookies use HttpOnly and SameSite protections.
- POST forms can use the shared CSRF token helpers in `config/bootstrap.php`.
- Database errors are logged instead of exposing SQL details to users.
- Local secrets and environment files are excluded from Git.

## Recommended next modules

Customers, Companies, Contacts, Leads, Deals, Products, Quotes, Sales, Tasks and Reports should share common authentication, validation, CSRF protection, pagination, search, flash messages and role-based authorization.
