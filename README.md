# ArveCRM

A PHP/MySQL CRM application for managing customers, companies and related sales workflows.

## Local setup

1. Install XAMPP with Apache, PHP and MySQL.
2. Clone this repository into `htdocs/arvecrm`.
3. Create a MySQL database named `crm_database`.
4. Copy `.env.dev.example` to `.env` in the project root and update the development database values if needed.
5. Import your CRM database schema into `crm_database`.
6. Open `/arvecrm/auth/login.php` in the browser.

## Environment switching

The application detects the current environment from `APP_ENV` (or `ENVIRONMENT`) and automatically chooses the matching database settings.

- `APP_ENV=development` loads `DB_HOST_DEV`, `DB_PORT_DEV`, `DB_NAME_DEV`, `DB_USER_DEV` and `DB_PASSWORD_DEV` if present.
- `APP_ENV=production` loads `DB_HOST_PROD`, `DB_PORT_PROD`, `DB_NAME_PROD`, `DB_USER_PROD` and `DB_PASSWORD_PROD` if present.
- If environment-specific variables are not set, it falls back to the regular `DB_*` values.
- If a `.env.<environment>` file exists, it is loaded automatically before the generic `.env` file.

Example files are included for both environments. Copy the appropriate example to `.env` and keep the real `.env` file out of Git:

- `.env.dev.example`
- `.env.prod.example`

## Hostinger deployment

1. Create a MySQL database and database user in hPanel, then grant that user access to the database. Hostinger commonly prefixes both names with your account identifier; copy the exact database name, username, host and port shown in hPanel.
2. Upload the application files to your hosting account. The web root is usually `public_html`; avoid placing application files in a publicly browsable directory.
3. Configure the environment values using the hosting environment-variable feature if available. Set `APP_ENV=production` and provide your production database variables. If using a file, copy `.env.prod.example` to `.env` and enter the real values from hPanel.
4. Keep `.env` outside `public_html` when possible. If it must be in the project web root, the included `.htaccess` denies HTTP access to `.env` files. Do not remove that protection.
5. Import the CRM schema/data into the Hostinger database using phpMyAdmin, then visit `/auth/login.php` at your hosted domain.

The application reads actual process/server environment variables first, then fills in missing values from `.env`. To use a `.env` file outside the project root, set `CRM_ENV_FILE` to its absolute server path. Never commit `.env` or share its database password.

## Security baseline

- PDO uses exceptions, native prepared statements and associative fetches.
- Authentication regenerates the session ID after successful login.
- Session cookies use HttpOnly and SameSite protections.
- POST forms can use the shared CSRF token helpers in `config/bootstrap.php`.
- Database errors are logged instead of exposing SQL details to users.
- Local secrets and environment files are excluded from Git.

## Recommended next modules

Customers, Companies, Contacts, Leads, Deals, Products, Quotes, Sales, Tasks and Reports should share common authentication, validation, CSRF protection, pagination, search, flash messages and role-based authorization.
