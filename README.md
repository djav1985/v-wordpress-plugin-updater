# V WordPress Plugin Updater

> A self-hosted update service and WordPress plugin for distributing plugin and theme packages.

## Table of Contents

1. [Overview](#overview)
2. [Features](#features)
3. [Project Structure](#project-structure)
4. [Getting Started](#getting-started)
5. [Usage](#usage)
6. [Development](#development)
7. [API Reference](#api-reference)
8. [Changelog](#changelog)
9. [License](#license)

## Overview

V WordPress Plugin Updater is a two-part update system. The Update API server stores authorized WordPress hosts, logs API activity, and serves plugin and theme ZIP packages. The WordPress client checks the API for updates and installs newer packages through WordPress updater classes.

Plugin and theme versions are read directly from package filenames. The Update API uses PHP 8.2 or later; the WordPress client declares PHP 8.0 or later.

## Features

| Area | Details |
| :--- | :--- |
| Update API | PHP MVC application using FastRoute, PSR-7 requests and responses, Doctrine DBAL, and SQLite. |
| WordPress client | Separate plugin and theme updaters integrate with WordPress update mechanisms and the admin settings widget. |
| Package management | Uploads and serves ZIP files named `{slug}_{version}.zip`; a new upload replaces the previous package for that slug. |
| Host security | Stores encrypted API keys, validates host access, and applies blacklist, session, and CSRF router guards. |
| Activity logs | Records API activity and retains the current day plus the previous six calendar days. |
| Update handling | Treats packages missing from this server as unmanaged and reuses the ZIP fetched during a successful update check. |
| Development | PHPUnit, PHP_CodeSniffer, PHPStan, ESLint, and Stylelint scripts are provided. |

## Project Structure

```text
v-wordpress-plugin-updater/
├── tests/                       # PHPUnit tests
├── v-update-api/                # Update API server
│   ├── app/                     # Controllers, core classes, helpers, models, and views
│   ├── public/                  # Web root and installer
│   ├── storage/                 # SQLite database, logs, and package storage
│   ├── config.php
│   └── cron.php                 # Daily blacklist and log cleanup
├── v-wp-updater/                # WordPress client plugin
│   ├── helpers/
│   ├── services/
│   ├── widgets/
│   ├── install.php
│   ├── uninstall.php
│   └── v-wp-updater.php
├── API.MD                       # Update API endpoint reference
├── composer.json                # Development dependencies and PHP scripts
├── package.json                 # JavaScript and CSS lint tools
├── CHANGELOG.md
└── README.md
```

## Getting Started

### Requirements

- PHP 8.2 or later for the Update API server
- PHP 8.0 or later for the WordPress client
- Apache, Nginx, or another PHP-capable web server
- A writable `v-update-api/storage/` directory
- Composer and Node.js 26 or later for development checks

### Install the Update API Server

1. Deploy the repository and set `v-update-api/public/` as the server document root.
2. Create writable storage directories:

   ```sh
   mkdir -p v-update-api/storage/plugins
   mkdir -p v-update-api/storage/themes
   mkdir -p v-update-api/storage/logs
   ```

3. Set deployment-specific `VALID_USERNAME`, `VALID_PASSWORD`, and `ENCRYPTION_KEY` values in `v-update-api/config.php`. The configured admin password is compared as entered; it is not a password hash.
4. Generate a 32-byte encryption key and store the generated hexadecimal value in `ENCRYPTION_KEY`:

   ```sh
   php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
   ```

   Keep this key backed up securely. Stored host API keys cannot be decrypted if the key is lost or changed.
5. Ensure the web server can write to `v-update-api/storage/`.
6. Initialize the SQLite database from the repository root, or visit `/install.php` on the API server:

   ```sh
   php v-update-api/public/install.php
   ```

   A `HOSTS` import adds new domains while preserving keys for existing domains.
7. Configure a daily cron job. It runs from the API directory and accepts no arguments:

   ```cron
   0 2 * * * cd /path/to/v-update-api && php cron.php
   ```

   The job removes expired blacklist entries and old API activity logs. Package versions are read from ZIP filenames, so uploads do not require a sync job.

### Install the WordPress Client

1. Copy `v-wp-updater/` into the WordPress installation's `wp-content/plugins/` directory and activate the plugin.
2. Set the API URL and host API key in the **V WordPress Updater Settings** dashboard widget. Provisioning can also set the options:

   ```php
   update_option('vwpu_update_plugin_url', 'https://updates.example.com/api');
   update_option('vwpu_update_theme_url', 'https://updates.example.com/api');
   update_option('vwpu_update_key', 'your-api-key-from-server');
   ```

3. Enable plugin updates, theme updates, or both in the settings widget. Both update types are disabled by default and run on the scheduled daily checks.

When a host is created or its API key is regenerated on the server, update the corresponding client configuration.

## Usage

### Manage Packages and Hosts

1. Sign in to the Update API admin panel using the credentials configured in `v-update-api/config.php`.
2. Add an authorized WordPress domain and generate its API key in the `/home` route.
3. Upload plugin ZIP files at `/plupdate` and theme ZIP files at `/thupdate`. Use the filename format `{slug}_{version}.zip`, for example `my-plugin_1.2.3.zip`.
4. Uploading another version for the same slug replaces the existing ZIP package.
5. Review update activity at `/logs`. The page covers the current day and the previous six calendar days.

The WordPress client requests updates only for the enabled update types. It skips packages the server does not manage and installs a newer package through the WordPress plugin or theme upgrader.

## Development

Install development dependencies from the repository root:

```sh
composer install
npm install
```

Available checks:

```sh
composer test
composer phpcs
composer phpstan
npm run lint
```

## API Reference

See [API.MD](./API.MD) for endpoint parameters, request examples, response codes, package naming, and security details.

## Changelog

See [CHANGELOG.md](./CHANGELOG.md) for release history and current changes.

## License

This project is licensed under the [MIT License](./LICENSE).
