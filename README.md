# DEVSMW Profiles

DEVSMW Profiles is a PHP/MySQL web app for searching and managing Malawi developer profiles.

## What Is Included

* Public searchable profile directory.
* Public profile pages.
* Admin login.
* Admin profile editor.
* One-click GitHub refresh per profile.
* MySQL schema.
* Markdown profile importer.
* Documentation for project goals, phases, data rules, UI, privacy, and terms.

## Local Setup (WAMP)

1. Start WAMP and make sure MySQL or MariaDB is running.
2. Copy the example environment file:

```powershell
Copy-Item .env.example .env
```

3. Edit `.env` and set your local database credentials. The database name must match the database created by `database/schema.sql` (`devsmw_profiles` by default).
4. Import the database schema:

```powershell
& 'C:\\wamp64\\bin\\mariadb\\mariadb11.4.9\\bin\\mysql.exe' -uroot < database\\schema.sql
```

5. Import existing markdown profiles:

```powershell
php scripts\\import_markdown_profiles.php
```

6. Open:

```text
http://localhost/DEVSMW/
```

The `.env` file is machine-specific and must never be committed. Production hosting must have its own environment variables or a private `.env` file configured outside version control. Do not copy production credentials into this repository.

## Admin Login

```text
http://localhost/DEVSMW/admin/login.php
Username: admin
Password: change-me-now
```

Change the default password from the admin panel before any public use:

```text
Admin Dashboard > Password
```

## Important Legal Note

The documents in `docs/` are starter project policies. They reduce risk by defining careful data rules, opt-out handling, and platform-compliant fetching, but they are not a substitute for legal advice.
