# Shantini Crackers

PHP/MySQL catalogue and estimate-request application.

## Configuration

Configure the database through the web server environment; do not commit credentials:

```text
DB_HOST=localhost
DB_NAME=fireworks_db
DB_USER=fireworks_app
DB_PASS=use-a-long-unique-password
APP_URL=https://example.com
APP_ENV=production
```

For an existing XAMPP local installation, `APP_ENV` defaults to `local` and uses its conventional `root` account with a blank password only when `DB_USER` is absent. Set `APP_ENV=production` on a deployed server; production requires `DB_USER` and `DB_PASS`.

Create a dedicated MySQL user with only the permissions required by this application's database. The project expects tables for users, admins, products, categories, brands, inquiries, shipping_details, and inquiry_items. Export and version-control your schema separately before deploying.

## Deployment notes

- Serve the application over HTTPS so session cookies receive the `Secure` flag.
- Configure a real mail transport before enabling password-reset emails.
- In local XAMPP mode, password-reset links are shown on the confirmation page because PHP mail delivery is not configured. This is intentionally disabled in production; configure a real SMTP/mail provider there.
- Ensure password hashes use `password_hash()`; plaintext legacy passwords are intentionally no longer accepted.
- Keep PHP error display disabled in production and inspect server logs for database failures.
- The uploads folder blocks direct access to text, log, and SQL files. Do not store customer inquiries or other private data in a web-accessible directory.
