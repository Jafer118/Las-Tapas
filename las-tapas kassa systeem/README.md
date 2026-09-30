# Las Tapas: Digital Ordering and Point-of-Sale System

Prototype developed for Gilde DevOps Solutions, MBO 4 Software Development.
The system replaces paper order slips with a digital ordering, kitchen, bar,
and cash-register workflow.

## Project Structure

```text
database/       MySQL schema, migrations, and sample data
backend/        PHP API endpoints, shared authentication, and PDO connection
frontend/       HTML screens, CSS, and shared JavaScript
```

The frontend handles presentation and user interaction. The PHP backend is the
authority for authentication, input validation, stock changes, order state,
and payment operations. Database changes belong in the SQL schema or a
documented migration. Existing API and database field names are retained for
compatibility with the current frontend and stored data.

## Local Setup

1. Import `database/las_tapas.sql` into MySQL using phpMyAdmin or the MySQL CLI. This creates the `las_tapas` database with tables and example data (tables and dishes). It also creates the `kassa_gebruikers` and `kassa_wachtwoord_resets` tables.
2. If the `las_tapas` database already exists, import `database/migrate_auth_tables.sql` first. This creates the separate cashier login tables while leaving the stock accounts unchanged.
3. Configure the connection values in `backend/db.php` for your local WAMP/XAMPP installation. The defaults target a local MySQL server with the `root` user and an empty password.
4. Place this project directory under the web server document root.
5. Open `http://localhost/las-tapas/frontend/login.html` and sign in with the sample admin account `admin@lastapas.nl` / `LasTapas123!`. Change the sample password immediately after the first login via the reset flow. Never use sample credentials in production.

## Main Workflows

- The ordering screen submits table, guest-count, optional email, and menu-item
  data. The API validates the request, checks table capacity and stock, saves
  order lines, and deducts inventory in one database transaction.
- Kitchen and bar screens poll for outstanding items every five seconds and
  allow staff to advance their preparation status.
- The cash-register screen displays the bill and closes the order. Receipts can
  be printed or downloaded as PDF. The Gmail handoff pre-fills the message;
  staff attach the downloaded PDF themselves because browsers cannot attach
  local files automatically.
- The menu contains 55 sample items across five menu groups. The floor plan has
  26 tables. Prices are placeholders and must be checked before real use.

## Engineering Conventions

- Write source comments and new internal identifiers in English. Dutch is kept
  for user-facing interface text and existing API/database names until those
  public contracts can be migrated together.
- Keep presentation in `frontend/`, request handling in `backend/api/`, shared
  authentication and response helpers in `backend/lib/`, and persistence
  definitions in `database/`.
- Use prepared PDO statements for values, validate untrusted input on the
  server, and use transactions when a workflow changes related records.
- Keep functions focused, use descriptive camelCase names for PHP variables and
  functions, and add comments only to explain non-obvious decisions.
- Return deliberate client errors for invalid input. Log unexpected server
  errors and return generic messages without database or stack details.

## Validation and Error Handling

Browser validation improves usability but is not a security boundary. The API
must independently validate request methods, JSON shape, identifiers, email
addresses, numeric ranges, and business rules. Order creation rolls back all
related writes when a table, menu item, or stock check fails. Unexpected
failures are logged by the server and return a generic response to the client.

Run PHP's syntax checker after changing backend files:

```powershell
Get-ChildItem backend -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

The project currently has no automated integration-test suite. Exercise login,
invalid order payloads, insufficient stock, successful ordering, and checkout
against a disposable local database before deployment.

## Security and Deployment Notes

- Passwords use PHP's `password_hash` and `password_verify`; the login flow
  regenerates the session ID, and session cookies use HttpOnly and SameSite
  settings.
- Reset tokens are random, stored as SHA-256 hashes, expire after 60 minutes,
  and are marked as used after a successful reset.
- The local prototype returns a reset URL in the API response so it can be
  tested without email. This exposes a valid reset link and is not suitable for
  production. Connect a mail provider, remove the token from API responses, and
  add abuse rate limiting before deployment.
- Use HTTPS in production, set a dedicated database account with least
  privilege, replace the sample admin credentials, and keep secrets outside
  version control. Do not expose PHP errors to clients.
- Review `frontend/privacy.html` and the data-retention policy before collecting
  real customer information.

## Database Changes

The SQL file includes the initial schema, sample menu, floor plan, and commented
migration examples. For an existing database, inspect the current schema and
apply only migrations that have not already run. Dropping the database deletes
all stored orders and should only be done with disposable data and a backup.