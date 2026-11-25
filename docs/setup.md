# Setup Guide

## Requirements

* WAMP
* PHP 8.0 or newer
* MySQL or MariaDB
* PHP extensions: PDO, pdo_mysql, mbstring, and cURL

## Database

Start WAMP's MySQL or MariaDB service first.

Import the schema from the project root:

```powershell
cmd /c '"C:\wamp64\bin\mariadb\mariadb11.4.9\bin\mysql.exe" -uroot < "database\schema.sql"'
```

Alternative MySQL path:

```powershell
cmd /c '"C:\wamp64\bin\mysql\mysql8.4.7\bin\mysql.exe" -uroot < "database\schema.sql"'
```

If your local MySQL account requires a password, add the appropriate MySQL command-line option when importing the schema.

## Application Configuration

Configuration is loaded from environment variables. For a local WAMP installation, create your private `.env` file from the example:

```powershell
Copy-Item .env.example .env
notepad .env
```

Set the database values to match your local WAMP installation:

```dotenv
APP_NAME="DEVSMW Profiles"
APP_URL=http://localhost/DEVSMW
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=devsmw_profiles
DB_USER=root
DB_PASS=
```

Replace `DB_PASS` with your local database password if one is configured. Keep `.env` private. It is ignored by Git and must never be committed. Do not put production credentials in the repository.

The application fails during bootstrap when required database settings are missing. Edit `.env`, not `config/config.php`.

## Optional Integrations

A GitHub token increases the API rate limit for profile refreshes. Add it to the `.env` file if you use GitHub refresh:

```dotenv
GITHUB_TOKEN=your_github_token
```

Keep the token private. Do not paste it into source files or commit it.

### WAMP TLS Certificate Configuration

If GitHub refresh fails with `unable to get local issuer certificate`, configure PHP's CA bundle rather than disabling SSL verification:

1. Download the current CA bundle from [curl.se/ca/cacert.pem](https://curl.se/ca/cacert.pem).
2. Save it outside the public project directory, for example:
   `C:\wamp64\bin\php\certs\cacert.pem`.
3. In the active Apache PHP `php.ini`, set:

   ```ini
   curl.cainfo = "C:/wamp64/bin/php/certs/cacert.pem"
   openssl.cafile = "C:/wamp64/bin/php/certs/cacert.pem"
   ```

4. Restart Apache through WAMP.

Keep certificate verification enabled in the application.

Bing search is optional. The default search engine is DuckDuckGo. To use Bing, set `SEARCH_ENGINE=bing` and provide `BING_SEARCH_KEY`.

## Import Profiles

After the schema and environment configuration are ready:

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

Before public use, sign in to the admin panel and change the default password:

```text
Admin Dashboard > Password
```

## Production Deployment

Configure the required `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` values in the hosting environment or in a private server-side `.env` file outside public access. Never overwrite the production configuration with local WAMP values.
