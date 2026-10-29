# Las Tapas Restaurant Systems

This README documents all applications and shared conventions in the
repository. The portal and this project documentation are grouped in
`projectinformatie/`.

- `Las Tapas/Frontend/` is the public restaurant website and reservation
  interface.
- `las-tapas kassa systeem/` is the digital ordering, kitchen, bar, and
  cash-register prototype developed for Gilde DevOps Solutions, MBO 4 Software
  Development. Its detailed workflows and assessment notes are documented
  below.
- `voorraad-las-tapas/` is the inventory-management application. It has its
  own PHP endpoints, login flow, and `voorraad.sql` database setup.

## Project Structure

```text
.editorconfig           Shared repository formatting conventions (root)
projectinformatie/
  index.html            Repository portal
  portal.css            Repository portal styles
  README.md             Shared setup and engineering documentation
Las Tapas/
  Frontend/             Restaurant website and reservation interface
  Backend/
    api/                Public reservation API
    lib/                Reservation validation and rate limiting
    tests/              Reservation validation checks
  Database/             Reservation database migration
las-tapas kassa systeem/
  database/     MySQL schema, migrations, and sample data
  backend/      PHP API endpoints, shared authentication, and PDO connection
  frontend/     HTML screens with separate CSS and JavaScript assets
  tests/        Lightweight validation and PDF checks
voorraad-las-tapas/
  auth.php      Inventory login handler
  db.php        Inventory PDO connection
  voorraad_api.php
                Inventory API
  voorraad.sql  Inventory schema and sample data
```

The restaurant website, point-of-sale system, and inventory manager are
separate application surfaces with different responsibilities. The website
contains the restaurant's public-facing content and reservation interface.
The point-of-sale frontend handles staff interaction, while its PHP backend is
the authority for authentication, input validation, order state, and payment
operations. The point-of-sale system and inventory application share
`voorraad_items` for stock quantities, so an order deducts stock visible in the
inventory screen. Database changes belong in the relevant application's SQL
schema or a documented migration.

The root `.editorconfig` intentionally remains at the repository root so its
formatting rules apply to every application. Portal content and shared
documentation are grouped in `projectinformatie/`. Internal identifiers and
comments are English; Dutch database identifiers and API field names remain
unchanged where they are existing contracts shared by the applications.

## Point-of-Sale Local Setup

1. Import `las-tapas kassa systeem/database/las_tapas.sql` into MySQL using
   phpMyAdmin or the MySQL CLI. This creates the `las_tapas` database with
   tables and example data (tables and dishes). It also creates the
   `kassa_gebruikers` and `kassa_wachtwoord_resets` tables.
2. Import `voorraad-las-tapas/voorraad.sql` into the same database. Ordering
   reads and deducts stock from `voorraad_items`, which is also managed by the
   inventory screen.
3. If the `las_tapas` database already exists, import
   `las-tapas kassa systeem/database/migrate_auth_tables.sql` to create the
   cashier login tables and
   `las-tapas kassa systeem/database/migrate_table_capacities.sql` to align
   the 26 table capacities with the restaurant website. The capacity migration
   can be safely run more than once. Also run
   `las-tapas kassa systeem/database/migrate_stock_sync_triggers.sql` to remove
   conflicting stock-sync triggers and ensure stock changes participate in
   order transactions.
4. Configure `LAS_TAPAS_DB_HOST`, `LAS_TAPAS_DB_NAME`, `LAS_TAPAS_DB_USER`, and
   `LAS_TAPAS_DB_PASS` in the web-server environment if the local defaults do
   not match your WAMP/XAMPP installation. Defaults are for a local MySQL
   server using `root` with an empty password.
5. Set `APP_ENV=development` in the local WAMP/XAMPP environment when serving
   over plain HTTP. This allows local session cookies and exposes reset links
   for testing without email. The default production mode suppresses reset-token
   disclosure and requires HTTPS session cookies.
6. Place the repository under the web server document root.
7. Open `las-tapas kassa systeem/frontend/login.html` under the repository's
   local web URL and sign in with the sample admin account
   `admin@lastapas.nl` / `LasTapas123!`. Change the sample password
   immediately after the first login via the reset flow. Never use sample
   credentials in production.

## Inventory Local Setup

1. Import `voorraad-las-tapas/voorraad.sql` into MySQL. This creates the
   `las_tapas` database and the inventory tables.
2. The inventory connection reads `LAS_TAPAS_DB_HOST`, `LAS_TAPAS_DB_NAME`,
   `LAS_TAPAS_DB_USER`, and `LAS_TAPAS_DB_PASSWORD`. For local WAMP only, it
   defaults to `localhost`, `las_tapas`, `root`, and an empty password.
3. Serve the repository through PHP and open
   `voorraad-las-tapas/login.php` under the repository's local web URL.

## Public Website Local Setup

1. Create the `las_tapas` database by importing the inventory schema or the
   point-of-sale schema if either has not been installed yet.
2. Run `Las Tapas/Database/migrate_reservations.sql` against that database to
   create the reservation tables and the 26-table capacity map.
3. The reservation backend reuses the inventory PDO connection. Configure
   `LAS_TAPAS_DB_HOST`, `LAS_TAPAS_DB_NAME`, `LAS_TAPAS_DB_USER`, and
   `LAS_TAPAS_DB_PASSWORD` for PHP. Set `LAS_TAPAS_RATE_LIMIT_SECRET` to a
   long random secret; do not use the local fallback in production.
4. Configure the PHP and MySQL server timezones for the restaurant's local
   timezone so the server's opening-hour checks and database retention dates
   agree with the reservation interface.
5. Schedule `php "C:\path\to\Las-Tapas\Las Tapas\Backend\cleanup_expired_reservations.php"`
   to run hourly. It removes reservations at least 90 days old and expired
   rate-limit records. Check the PHP error log if the task exits unsuccessfully.
6. Serve the repository through PHP and open
   `http://localhost/php/Las-Tapas/Las%20Tapas/Frontend/`.
   Reservations are written only after the user confirms them. No real payment
   is accepted or processed.

`Las Tapas/Frontend/database/las_tapas.sql` is a legacy copy of the inventory
schema with different sample password hashes. Use
`voorraad-las-tapas/voorraad.sql` as the canonical setup for inventory; do not
import both copies, because whichever runs first determines the seeded login
passwords. The point-of-sale schema also targets the `las_tapas` database, but
uses separate cashier and order tables.

Paths in the remaining point-of-sale sections are relative to
`las-tapas kassa systeem/`.

The shared portal is available through WAMP at
`http://localhost/php/Las-Tapas/projectinformatie/` when the repository is
installed at `C:\wamp64\www\php\Las-Tapas`.

## Main Workflows

- Staff use `frontend/overzicht.html` as the single-page work panel for placing
  orders, preparing kitchen and bar items, and checking out tables. The panels
  are grouped into three task tabs to keep the daily interface focused.
- Table seating limits match the restaurant website: tables 1–3 seat 2, tables
  4–7 seat 4, tables 8–10 seat 6, and tables 11–26 seat 8. The API reads these
  capacities from `tafels` and enforces them when an order is submitted.
- Ordering submits table, guest-count, optional email, and menu-item data. The
  API validates the request, checks table capacity and stock, saves order lines,
  and deducts inventory in one database transaction.
- Kitchen and bar orders refresh every five seconds in the work panel and allow
  staff to advance each order line through preparation and serving.
- Checkout closes the order and frees the table. Receipts can be printed or
  downloaded as PDF. The Gmail handoff pre-fills the message; staff attach the
  downloaded PDF themselves because browsers cannot attach local files
  automatically.
- The public restaurant website validates reservations in the browser and
  independently on the server, checks table capacity and overlapping bookings,
  and stores confirmed reservations. Its review screen is an explicit
  confirmation demo: no payment provider is connected and no payment is
  processed.
- The menu contains 55 sample items across five menu groups. The floor plan has
  26 tables. Prices are placeholders and must be checked before real use.

## Engineering Conventions

- Write source comments, internal PHP/JavaScript identifiers, and developer
  documentation in English. Use camelCase for PHP and JavaScript functions and
  variables, and kebab-case for CSS classes and custom properties. Dutch is
  appropriate for visible interface copy.
- Preserve established Dutch API route names, request/response keys, database
  tables and columns, and filenames that are part of existing links. These are
  compatibility contracts rather than internal identifiers; translating them
  requires a coordinated migration across the database, backend, and clients.
- Keep presentation in `frontend/`, request handling in `backend/api/`, shared
  authentication and response helpers in `backend/lib/`, and persistence
  definitions in `database/` for the point-of-sale system. Keep each other
  application's assets and backend code within its own top-level directory.
- Use prepared PDO statements for values, validate untrusted input on the
  server, and use transactions when a workflow changes related records.
- Render API or database text with `textContent` or context-appropriate HTML
  escaping. Validate numeric identifiers before using them in UI actions.
- Follow the repository-root `.editorconfig` for UTF-8, final newlines,
  whitespace, indentation, and line endings. The existing restaurant website
  uses two-space indentation, explicitly configured for its HTML, JavaScript,
  and CSS files. The inventory stylesheet and legacy point-of-sale landing
  page also retain their explicitly configured two-space indentation. Use
  four spaces elsewhere, including PHP. Keep PHP strict typing enabled,
  functions focused, and implementation identifiers descriptive and
  camelCase.
- Add comments only to explain non-obvious decisions or invariants. For example,
  document why a table row is locked, why a keyed digest is stored instead of a
  client address, or how PDF byte offsets are calculated. Keep setup,
  architectural boundaries, security assumptions, and verification documented
  here; comments should complement rather than repeat names or documentation.
- Return deliberate client errors for invalid input. Log unexpected server
  errors and return generic messages without database or stack details.

### Applying the conventions to a change

For each requested change, first identify the responsible layer and follow this
sequence:

1. Put page structure in the relevant HTML file, presentation in its CSS file,
   browser behavior in its JavaScript file, and authoritative validation or
   persistence in the relevant PHP API/helper. Do not add new inline CSS or
   JavaScript.
2. Name new internal functions and variables in English `camelCase`, CSS
   classes in English `kebab-case`, and keep visible Dutch copy in the UI.
   Preserve Dutch route, request/response, and database names when they are
   existing integration contracts.
3. Validate untrusted values on the server even when the browser also checks
   them. Use the existing validation, response, and authentication helpers
   rather than creating a competing pattern.
4. Add a comment only when the reason or invariant is not clear from the code.
   Update this document when a setup step, shared boundary, security
   assumption, or test command changes.
5. Run the focused test suite for the affected application, syntax-check
   changed PHP/JavaScript, and manually exercise the changed user workflow.

For example, adding a menu interaction means keeping its markup in the page,
styles in `frontend/css/restaurant.css`, and event handling in
`frontend/js/restaurant.js`; any submitted data still needs server-side
validation in the API. This makes the conventions usable when implementing a
feature, not just a list to recite.

The public website frontend is a single-page application: its script is
organized into named sections for navigation, menu, ordering, reservation,
and opening-hours behavior. Reservation persistence and business rules are
isolated in the PHP API, validation/rate-limit helpers, and database migration.
This keeps browser presentation separate from authoritative data checks.

## Validation and Error Handling

Browser validation improves usability but is not a security boundary. The API
independently validates request methods, JSON shape, identifiers, email
addresses, numeric ranges, legal order-state transitions, and business rules.
Order creation and checkout use transactions and row locks so related state
changes are committed together. Unexpected exceptions are logged server-side
and return a generic response to the client. Shared frontend API helpers handle
network failures and invalid JSON responses; workflows display validation and
business-rule errors returned by the API.

Run PHP's syntax checker after changing point-of-sale backend files and run
its validation/PDF checks:

```powershell
Get-ChildItem "las-tapas kassa systeem\backend" -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
php "las-tapas kassa systeem\tests\run.php"
```

The lightweight point-of-sale checks cover validation and PDF output without
requiring a live database. Also exercise login, CSRF rejection, invalid order
payloads, insufficient stock, legal and illegal status transitions, successful
ordering, and checkout against a disposable local database before deployment.
Run `php -l` on changed PHP files under `voorraad-las-tapas/` to check the
inventory application, and run its input-validation checks with:

```powershell
php "voorraad-las-tapas\tests\run.php"
```

Run the public reservation input-validation checks with:

```powershell
php "Las Tapas\Backend\tests\run.php"
```

These checks do not require a database. Before deployment, also test the API's
CSRF and rate-limit behavior, table capacity, overlapping reservations, and
cleanup job against a disposable MySQL database.

## Security and Deployment Notes

- Passwords use PHP's `password_hash` and `password_verify`; the login flow
  regenerates the session ID, and session cookies use HttpOnly and SameSite
  settings.
- Inventory-changing requests require a session-bound CSRF token. The inventory
  backend validates item fields and request sizes independently of browser
  checks, uses prepared statements for database writes, and limits login
  attempts to 10 per client address every 15 minutes. Set
  `LAS_TAPAS_RATE_LIMIT_SECRET` to a random value of at least 32 characters in
  production; all three applications reject a missing or shorter production
  secret rather than silently using the local fallback. Re-import
  `voorraad-las-tapas/voorraad.sql` on an existing database to create its login
  rate-limit table; its `CREATE TABLE IF NOT EXISTS` statements are repeatable.
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
- Public reservation requests are JSON, capped at 16 KiB, session-CSRF
  protected, rate-limited, and validated on the server. Server-side prices and
  package durations are authoritative. A transaction and table row lock prevent
  overlapping bookings from being accepted concurrently.
- In production, explicitly set `APP_ENV=production` and provide a
  `LAS_TAPAS_RATE_LIMIT_SECRET` containing at least 32 characters. Each
  application rejects a missing or shorter secret in production. Local
  development retains the existing fallback.
- Public reservation personal data is removed when its reservation date is at
  least 90 days old. Schedule the documented cleanup task; POST requests also
  remove expired reservations, but scheduled cleanup enforces retention even
  when the site is idle.
- Review `las-tapas kassa systeem/frontend/privacy.html` and the data-retention
  policy before collecting real customer information. This prototype's
  privacy text is not a substitute for legal review.

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