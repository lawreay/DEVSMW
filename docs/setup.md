# Setup Guide

## Requirements

* WAMP
* PHP 8.0 or newer
* MySQL or MariaDB
* PHP extensions: PDO, pdo_mysql, mbstring

## Database

Start WAMP's MySQL or MariaDB service first.

Import the schema:

```powershell
& 'C:\wamp64\bin\mariadb\mariadb11.4.9\bin\mysql.exe' -uroot < database\schema.sql
```

Alternative MySQL path:

```powershell
& 'C:\wamp64\bin\mysql\mysql8.4.7\bin\mysql.exe' -uroot < database\schema.sql
```

## App Config

Edit `config/config.php` if your database password or port is different.

Before public use, change:

```php
'password' => 'change-me-now',
```

## Import Profiles

After the schema exists:

```powershell
php scripts\import_markdown_profiles.php
```

## Run

Open:

```text
http://localhost/DEVSMW/
```

Admin:

```text
http://localhost/DEVSMW/admin/login.php
```

## Optional GitHub Token

For better GitHub API rate limits, set `GITHUB_TOKEN` before using the one-click refresh.

```powershell
$env:GITHUB_TOKEN = 'your_token_here'
```
