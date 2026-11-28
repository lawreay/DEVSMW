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

The application reads configuration from environment variables. For local WAMP development, create a private `.env` file from the committed template:

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

Set `DB_PASS` to your local MySQL password when one is configured. Keep `.env` private. Git ignores it; only `.env.example` belongs in the repository.

The application reports a configuration error during bootstrap if required database values are missing. Edit `.env`, not `config/config.php`.

## Optional Integrations

A GitHub token can increase the API rate limit for profile refreshes. Add it to your private `.env` file if needed:

```dotenv
GITHUB_TOKEN=your_github_token
```

Do not paste real tokens into source files or commit them.

Bing search is optional. DuckDuckGo is the default. To use Bing, set:

```dotenv
SEARCH_ENGINE=bing
BING_SEARCH_KEY=your_bing_search_key
```

## WAMP TLS Certificate Configuration

If GitHub refresh fails with `unable to get local issuer certificate`, configure PHP's CA bundle rather than disabling SSL verification:

1. Download the current CA bundle from [curl.se/ca/cacert.pem](https://curl.se/ca/cacert.pem).
2. Save it outside the public project directory, for example: `C:\wamp64\bin\php\certs\cacert.pem`.
3. In the active Apache PHP `php.ini`, set:

   ```ini
   curl.cainfo = "C:/wamp64/bin/php/certs/cacert.pem"
   openssl.cafile = "C:/wamp64/bin/php/certs/cacert.pem"
   ```

4. Restart Apache through WAMP.

Keep certificate verification enabled in the application.

## Import Profiles

After importing the schema and configuring `.env`, run:

```powershell
php scripts\import_markdown_profiles.php
```

## Run

Open the application:

```text
http://localhost/DEVSMW/
```

Admin login:

```text
http://localhost/DEVSMW/admin/login.php
```

Use the administrator credentials configured for your installation. Change any initial password before public use.

## Production Deployment

Configure `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` in the hosting environment or in a private server-side `.env` file outside public access. Do not copy local WAMP values over production settings. Never commit production credentials.
