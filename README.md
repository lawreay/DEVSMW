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

## Local Setup

1. Start WAMP and make sure MySQL or MariaDB is running.
2. Import the database schema:

```powershell
& 'C:\wamp64\bin\mariadb\mariadb11.4.9\bin\mysql.exe' -uroot < database\schema.sql
```

3. Import existing markdown profiles:

```powershell
php scripts\import_markdown_profiles.php
```

4. Open:

```text
http://localhost/DEVSMW/
```

5. Admin login:

```text
http://localhost/DEVSMW/admin/login.php
Username: admin
Password: change-me-now
```

Change the default password in `config/config.php` before any public use.

## Important Legal Note

The documents in `docs/` are starter project policies. They reduce risk by defining careful data rules, opt-out handling, and platform-compliant fetching, but they are not a substitute for legal advice.
