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
3. Configure `LAS_TAPAS_DB_HOST`, `LAS_TAPAS_DB_NAME`, `LAS_TAPAS_DB_USER`, and
  `LAS_TAPAS_DB_PASS` in the web-server environment if the local defaults do
  not match your WAMP/XAMPP installation. Defaults are for a local MySQL
  server using `root` with an empty password.
4. Set `APP_ENV=development` in the local WAMP/XAMPP environment when serving
  over plain HTTP. This allows local session cookies and exposes reset links
  for testing without email. The default production mode suppresses reset-token
  disclosure and requires HTTPS session cookies.
5. Place this project directory under the web server document root.
6. Open `http://localhost/las-tapas/frontend/login.html` and sign in with the sample admin account `admin@lastapas.nl` / `LasTapas123!`. Change the sample password immediately after the first login via the reset flow. Never use sample credentials in production.

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

- Write source comments, PHP/JavaScript identifiers, and documentation in
  English. Dutch remains in visible interface copy and current API/database
  field names; changing those public contracts requires a coordinated migration.
- Keep presentation in `frontend/`, request handling in `backend/api/`, shared
  authentication and response helpers in `backend/lib/`, and persistence
  definitions in `database/`.
- Use prepared PDO statements for values, validate untrusted input on the
  server, and use transactions when a workflow changes related records.
- Render API or database text with `textContent` or context-appropriate HTML
  escaping. Validate numeric identifiers before using them in UI actions.
- Use four spaces and UTF-8. PHP uses LF; HTML, JavaScript, CSS, SQL, and Markdown
  use CRLF. These rules and final newlines are configured in `.editorconfig`.
  Keep functions focused and use descriptive camelCase names.
- Add comments only to explain non-obvious decisions or invariants; document
  setup, architectural boundaries, security assumptions, and verification here.
- Return deliberate client errors for invalid input. Log unexpected server
  errors and return generic messages without database or stack details.

## Validation and Error Handling

Browser validation improves usability but is not a security boundary. The API
independently validates request methods, JSON shape, identifiers, email
addresses, numeric ranges, legal order-state transitions, and business rules.
Order creation and checkout use transactions and row locks so related state
changes are committed together. Unexpected exceptions are logged server-side
and return a generic response to the client. Shared frontend API helpers handle
network failures and invalid JSON responses; workflows display validation and
business-rule errors returned by the API.

Run PHP's syntax checker after changing backend files:

```powershell
Get-ChildItem backend -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
php tests/run.php
```

The lightweight checks in `tests/` cover validation and PDF output without
requiring a live database. Also exercise login, CSRF rejection, invalid order
payloads, insufficient stock, legal and illegal status transitions, successful
ordering, and checkout against a disposable local database before deployment.

## Security and Deployment Notes

- Passwords use PHP's `password_hash` and `password_verify`; the login flow
  regenerates the session ID, and session cookies use HttpOnly and SameSite
  settings.
- Authenticated state-changing requests require a random session-bound CSRF
  token; login and password-reset forms also use the anonymous session token.
  APIs reject unsupported HTTP methods, cap JSON bodies at 1 MiB, and unexpected
  exceptions do not disclose database details.
- Login is limited to 10 requests per client address every 15 minutes. Reset
  requests and token attempts are each limited to 5 per client address per
  hour. Set `LAS_TAPAS_RATE_LIMIT_SECRET` to a long random value in production.
- Reset tokens are random, stored as SHA-256 hashes, expire after 60 minutes,
  and are marked as used after a successful reset.
- Reset URLs are returned only when `APP_ENV=development`; the default
  production mode never returns a valid token in the API response. Connect a
  mail provider before enabling password resets in production.
- Use HTTPS in production, set a dedicated database account with least
  privilege, replace the sample admin credentials, and keep secrets outside
  version control. Do not expose PHP errors to clients.
- Review `frontend/privacy.html` and the data-retention policy before collecting
  real customer information.

## Oral Assessment Preparation

Be ready to explain these five topics in your own words and point to the
implementation:

1. Why are prepared PDO statements used, and which values can they protect?
2. Why are stock changes and order-line inserts in the same transaction?
3. How does the session-bound CSRF token protect a POST request before and after login?
4. Why does the server repeat validation that the browser already performs?
5. How does the PDF generator calculate object offsets and the cross-reference
  table, and why must those offsets use byte lengths?

Short answer notes:

- Prepared statements bind data values so input cannot change SQL syntax. They
  do not bind table or column names; those must come from trusted code.
- A transaction makes related inserts and stock updates atomic. On failure,
  rollback prevents an order from being partially saved or inventory drifting.
- The browser gets a random token tied to its PHP session and sends it in a
  custom header. The server compares both values with `hash_equals`; a foreign
  site cannot read the same-origin token to forge an authenticated mutation.
- Browser checks can be bypassed or modified. Server checks enforce rules for
  every client and protect the database boundary.
- PDF xref entries are byte offsets into the encoded PDF. Character counts are
  not byte counts for UTF-8/WinAnsi text, so offsets must use byte lengths.

Use these as study notes, not a script to memorize. The assessment still
requires you to explain the choices and answer all five questions yourself.

## Database Changes

The SQL file includes the initial schema, sample menu, floor plan, and commented
migration examples. For an existing database, inspect the current schema and
apply only migrations that have not already run. Dropping the database deletes
all stored orders and should only be done with disposable data and a backup.