# DEVSMW Profiles

[![PHP Lint](https://github.com/lawreay/DEVSMW/actions/workflows/php-lint.yml/badge.svg?branch=main)](https://github.com/lawreay/DEVSMW/actions/workflows/php-lint.yml) [![Stars](https://img.shields.io/github/stars/lawreay/DEVSMW?style=flat)](https://github.com/lawreay/DEVSMW/stargazers) [![Forks](https://img.shields.io/github/forks/lawreay/DEVSMW?style=flat)](https://github.com/lawreay/DEVSMW/network/members) [![Last Commit](https://img.shields.io/github/last-commit/lawreay/DEVSMW?style=flat)](https://github.com/lawreay/DEVSMW/commits/main)

DEVSMW Profiles is a PHP/MySQL web app for searching and managing Malawi developer profiles.

## Public Profile Privacy

Private projects are excluded from public profile pages. Only projects marked as public are eligible to appear in the public project list.

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
2. Create your private environment file:

```powershell
Copy-Item .env.example .env
notepad .env
```

3. Set the database values in `.env` to match your local WAMP installation.
4. Import the database schema from the project root:

```powershell
cmd /c '"C:\wamp64\bin\mariadb\mariadb11.4.9\bin\mysql.exe" -uroot < "database\schema.sql"'
```

5. Import existing Markdown profiles:

```powershell
php scripts\import_markdown_profiles.php
```

6. Open the application at `http://localhost/DEVSMW/`.

The private `.env` file is machine-specific and must never be committed. Use environment variables or a private server-side configuration file for production. Never put production credentials in this repository.

## Admin Login

Open `http://localhost/DEVSMW/admin/login.php` and use the administrator credentials configured for your installation. Change any initial password immediately. Never deploy with a shared or documented default password.

## Important Legal Note

The documents in `docs/` are starter project policies. They reduce risk by defining careful data rules, opt-out handling, and platform-compliant fetching, but they are not a substitute for legal advice.
