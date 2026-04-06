<div id="top">

<!-- HEADER STYLE: CLASSIC -->
<div align="center">

<img src="v-wordpress-plugin-updater.png" width="60%" alt="project-logo">

# V-WORDPRESS-PLUGIN-UPDATER

<em>Seamless Updates, Limitless Innovation, Unmatched Control</em>

<!-- BADGES -->
<img src="https://img.shields.io/github/license/djav1985/v-wordpress-plugin-updater?style=flat-square&logo=opensourceinitiative&logoColor=white&color=0080ff" alt="license">
<img src="https://img.shields.io/github/last-commit/djav1985/v-wordpress-plugin-updater?style=flat-square&logo=git&logoColor=white&color=0080ff" alt="last-commit">
<img src="https://img.shields.io/github/languages/top/djav1985/v-wordpress-plugin-updater?style=flat-square&color=0080ff" alt="repo-top-language">
<img src="https://img.shields.io/github/languages/count/djav1985/v-wordpress-plugin-updater?style=flat-square&color=0080ff" alt="repo-language-count">

<em>Built with the tools and technologies:</em>

<img src="https://img.shields.io/badge/JSON-000000.svg?style=flat-square&logo=JSON&logoColor=white" alt="JSON">
<img src="https://img.shields.io/badge/PHP-777BB4.svg?style=flat-square&logo=PHP&logoColor=white" alt="PHP">

</div>
<br>

---

## Table of Contents

1. [Table of Contents](#table-of-contents)
2. [Overview](#overview)
3. [Features](#features)
4. [Project Structure](#project-structure)
5. [Getting Started](#getting-started)
6. [Roadmap](#roadmap)
7. [License](#license)

---

## Overview

The V-WordPress-Plugin-Updater is a **dual-component system** designed to streamline WordPress plugin and theme updates through a centralized management approach:

1. **Update API Server** (`v-update-api/`): A standalone PHP web application that hosts and serves plugin/theme update packages. Built with a modern MVC architecture using FastRoute for routing, Doctrine DBAL for SQLite database management, and comprehensive security features including encrypted API keys, IP blacklisting, and session management.

2. **WordPress Client Plugin** (`v-wp-updater/`): A WordPress plugin that automatically checks for and installs updates from the API server. It integrates seamlessly with WordPress core update mechanisms, providing automated daily update checks, a dashboard settings widget for configuration, and comprehensive logging.

This architecture enables centralized control over plugin and theme updates across multiple WordPress installations, reducing manual maintenance overhead while maintaining security and reliability. The system supports both single-site and multisite WordPress installations and provides detailed logging and monitoring capabilities through an intuitive web-based admin interface.

---

## Features

|      | Component            | Details                                                                                     |
| :--- | :------------------- | :------------------------------------------------------------------------------------------ |
| ⚙️  | **Architecture**     | <ul><li>Dual-component system: standalone Update API server + WordPress client plugin</li><li>MVC architecture with FastRoute routing and Doctrine DBAL</li><li>Separate namespaces: `App\` (server) and `VWPU\` (client)</li></ul> |
| 🔩 | **Code Quality**     | <ul><li>PSR-12 coding standards for API server</li><li>WordPress Coding Standards for client plugin</li><li>PHPStan static analysis at level 6</li><li>Comprehensive PHPUnit test coverage</li></ul> |
| 📄 | **Documentation**    | <ul><li>Detailed README with installation and usage instructions</li><li>Inline PHPDoc comments throughout codebase</li></ul> |
| 🔌 | **Integrations**      | <ul><li>WordPress hooks and filters integration</li><li>Cron-based synchronization between filesystem and database</li></ul> |
| 🧩 | **Modularity**        | <ul><li>Separate controllers for API, login, hosts, plugins, themes, and logs</li><li>Helper classes for cron synchronization, encryption, validation, and message handling</li><li>Model layer for database operations (plugins, themes, hosts, logs, blacklist)</li></ul> |
| 🧪 | **Testing**           | <ul><li>PHPUnit test suite for both components</li><li>Tests for routing, database, session management, and updater logic</li><li>Namespace-based mocking for isolated unit tests</li></ul> |
| ⚡️  | **Performance**       | <ul><li>SQLite database for efficient metadata storage</li><li>Asynchronous update processing per plugin/theme</li><li>Daily cron synchronization job for database/file parity</li></ul> |
| 🛡️ | **Security**          | <ul><li>Encrypted API keys using AES-256-GCM (with legacy ciphertext migration)</li><li>IP-based blacklisting for repeated authentication failures</li><li>Session timeout and user agent validation</li><li>CSRF protection on non-API forms</li><li>Input validation and sanitization</li></ul> |
| 📦 | **Dependencies**      | <ul><li>PHP 8.2+ for `v-update-api` and PHP 8.0+ for `v-wp-updater`</li><li>Composer packages: FastRoute, Doctrine DBAL, Respect/Validation</li><li>WordPress core functions for client plugin</li><li>Web server with PHP support (Apache/Nginx)</li></ul> |

---

## Project Structure

```sh
└── v-wordpress-plugin-updater/
    ├── .github/
    │   └── copilot-instructions.md
    ├── LICENSE
    ├── README.md
    ├── tests/
    ├── v-update-api/                         # Update API server
    │   ├── app/
    │   │   ├── Controllers/
    │   │   ├── Core/
    │   │   │   ├── DatabaseManager.php
    │   │   │   ├── ErrorManager.php
    │   │   │   ├── Request.php
    │   │   │   ├── Response.php
    │   │   │   └── Router.php
    │   │   ├── Helpers/
    │   │   ├── Models/
    │   │   └── Views/
    │   ├── public/
    │   │   ├── index.php
    │   │   └── install.php
    │   ├── storage/
    │   │   ├── logs/
    │   │   ├── plugins/
    │   │   ├── themes/
    │   │   └── updater.sqlite
    │   ├── composer.json
    │   ├── config.php
    │   └── cron.php
    └── v-wp-updater/                        # WordPress client plugin
    │   ├── helpers/
    │   ├── services/
    │   ├── widgets/
    │   ├── install.php
    │   ├── uninstall.php
    │   └── v-wp-updater.php                # Main plugin file
```

---

## Getting Started
**System Requirements:**

* **PHP**: version 8.2 or higher for `v-update-api/`; version 8.0 or higher for `v-wp-updater/`
* **Web Server**: Apache, Nginx or any server capable of running PHP
* **Write Permissions**: ensure the web server can write to `/storage`

### Installation

#### Update API Server Setup

1. Clone or download this repository to your web server.
2. Set `v-update-api/public/` as your web server document root.
3. Create the following directories so the Update API can store packages and logs:

   ```sh
   mkdir -p v-update-api/storage/plugins
   mkdir -p v-update-api/storage/themes
   mkdir -p v-update-api/storage/logs
   ```

4. Edit `v-update-api/config.php` and set the login credentials and directory constants. Adjust `VALID_USERNAME`, `VALID_PASSWORD`, `LOG_FILE`, and paths under `BASE_DIR` if the defaults do not match your setup.
   The Update API requires PHP 8.2 or higher.

5. Set the `ENCRYPTION_KEY` constant in `v-update-api/config.php` to secure host keys (AES-256-GCM encryption):

	```sh
	# In v-update-api/config.php
   define('ENCRYPTION_KEY', 'replace-with-a-long-random-secret');
   ```

6. Ensure the web server user owns the `v-update-api/storage/` directory so uploads and logs can be written. Application logs are written to `LOG_FILE` (default `v-update-api/storage/logs/app.log`).

7. Navigate to `v-update-api/public/` and run `php install.php` in your browser or via CLI to create the SQLite database and required tables. Ensure `v-update-api/storage/updater.sqlite` is writable by the web server.

8. Configure a system cron to run once daily (the script is CLI-only and takes no arguments):

   ```sh
	0 2 * * * cd /path/to/v-update-api && php cron.php
   ```

	This keeps the database in sync with plugin and theme ZIP files in the storage directories and also cleans up expired blacklist entries.

#### WordPress Client Plugin Setup

1. Copy the `v-wp-updater/` directory to your WordPress installation's `wp-content/plugins/` directory.

2. Configure the API server URL and API key in WordPress using the dashboard settings widget or via provisioning:

   ```php
   update_option('vwpu_update_plugin_url', 'https://updates.example.com/api');
   update_option('vwpu_update_theme_url', 'https://updates.example.com/api');
   update_option('vwpu_update_key', 'your-api-key-from-server');
   ```

   Alternatively, navigate to the WordPress Dashboard → Settings widget (V WordPress Updater Settings) after activation to configure these values via the admin UI.

3. Activate the plugin through the WordPress admin panel or WP-CLI.

4. The plugin will automatically schedule daily update checks for plugins and themes.

**Note:** When a host entry is created or its key regenerated in the Update API admin panel, update the client installation with the new key using your provisioning process.

### Usage

#### Managing the Update API Server

1. Log in to the Update API admin panel by visiting `https://your-update-server.com/login` using the credentials configured in `v-update-api/config.php`.

2. **Manage Hosts**: Add authorized WordPress domains and generate API keys in the `/home` route.

3. **Upload Plugins**: Navigate to `/plupdate` to upload plugin ZIP files. Files must be named following the pattern `{slug}_{version}.zip` (e.g., `my-plugin_1.2.3.zip`).

4. **Upload Themes**: Navigate to `/thupdate` to upload theme ZIP files. Files must follow the same naming pattern as plugins.

5. **View Logs**: Check `/logs` for plugin and theme update activity logs.

6. The daily cron job will automatically sync uploaded files to the database. Ensure the cron job is configured as described in the installation steps.

#### Using the WordPress Client Plugin

Once activated, the V WordPress Plugin Updater automatically:

- Schedules daily update checks for all installed plugins and themes
- Contacts the Update API server to check for available updates
- Downloads and installs updates when newer versions are available
- Logs all update activities for troubleshooting

You can manually trigger update checks or view logs through the plugin's settings page in the WordPress admin panel.

---

## API Specification

The Update API provides endpoints for checking and retrieving plugin and theme updates.

### API Endpoint

**Base URL:** `/api`

**Method:** `GET`

### Required Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `type` | string | Type of update (plugin or theme) | `plugin` or `theme` |
| `domain` | string | Domain making the request | `example.com` |
| `key` | string | API key for authentication | `your-api-key` |
| `slug` | string | Plugin or theme slug | `my-plugin` |
| `version` | string | Current installed version | `1.0.0` |

### Response Codes

| Code | Description |
|------|-------------|
| `200 OK` | Update available, returns update package |
| `204 No Content` | No update available, current version is up to date |
| `400 Bad Request` | Missing/invalid request parameters (does not consume blacklist budget) |
| `403 Forbidden` | Invalid authentication credentials, unknown domain, or IP blacklisted |
| `404 Not Found` | Authenticated request references an unknown plugin/theme slug |
| `405 Method Not Allowed` | Request used a non-GET method |
| `500 Internal Server Error` | Update file is missing/unreadable on server |

### Example Request

```
GET /api?type=plugin&domain=example.com&key=your-api-key&slug=my-plugin&version=1.0.0
```

### Example Response (Update Available)

**Status:** `200 OK`

**Headers:**
```
Content-Type: application/octet-stream
Content-Disposition: attachment; filename="my-plugin_1.1.0.zip"
```

**Body:** Binary ZIP file contents

### Example Response (No Update)

**Status:** `204 No Content`

No response body.

### Client Behavior

The WordPress client plugin (`v-wp-updater`) implements this contract in
`PluginUpdater::fetch_package` and `ThemeUpdater::fetch_package`:

- Sends `type=plugin` or `type=theme` (not a separate `plugin`/`theme` parameter).
- Sends `slug=<plugin-or-theme-slug>` for the resource identifier (including single-file plugin slugs).
- On `200`: stores the ZIP response body in a temporary local file and reuses that file during install (avoids a second download).
- On `204`: returns `no_update`; no installation is attempted.
- On `403`: returns `unauthorized`; the failure is logged. (`401` is not returned by this API.)
- On `404`: returns `error` (authenticated slug not found).
- On any other code or network error: returns `error`.

### Security

- All requests are logged with domain, date, and status
- Failed authentication attempts (unknown domain / wrong key) are tracked per IP address
- Malformed requests (`400`) and authenticated unknown slugs (`404`) do not increment blacklist attempts
- IPs are automatically blacklisted after 3 failed authentication attempts
- Blacklisted IPs are automatically removed after 7 days
- Non-blacklisted IPs with no activity are removed after 3 days

### Rate Limiting

The API uses IP-based blacklisting for rate limiting. After 3 failed authentication attempts, an IP will be blacklisted for 7 days.

---

## Roadmap

- [X] **`Task 1`**: <strike>Convert to MVC framework</strike>
- [X] **`Task 2`**: <strike>Implement more advanced authorization for site connections</strike>
- [X] **`Task 3`**: <strike>Implement Database instead of useing filesystem</strike>
      
---

## License

V-wordpress-plugin-updater is protected under the [LICENSE](https://choosealicense.com/licenses) License. For more details, refer to the [LICENSE](https://choosealicense.com/licenses/) file.

---
