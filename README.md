<p align="center">
  <img src="v-wordpress-plugin-updater.png" width="60%" alt="project-logo">
</p>
<p align="center">
    <h1 align="center">V-WORDPRESS-PLUGIN-UPDATER</h1>
</p>
<p align="center">
    <em>Effortless Updates, Enhanced Security, Seamless WordPress Management</em>
</p>
<p align="center">
	<!-- local repository, no metadata badges. -->
<p>
<p align="center">
		<em>Developed with the software and tools below.</em>
</p>
<p align="center">
	<img src="https://img.shields.io/badge/PHP-777BB4.svg?style=flat-square&logo=PHP&logoColor=white" alt="PHP">
	<img src="https://img.shields.io/badge/JSON-000000.svg?style=flat-square&logo=JSON&logoColor=white" alt="JSON">
</p>

<br><!-- TABLE OF CONTENTS -->
<details>
  <summary>Table of Contents</summary><br>

- [📍 Overview](#-overview)
- [🧩 Features](#-features)
- [🗂️ Repository Structure](#️-repository-structure)
- [📦 Modules](#-modules)
- [🚀 Getting Started](#-getting-started)
  - [⚙️ Installation](#️-installation)
  - [🤖 Usage](#-usage)
- [🎗 License](#-license)
</details>
<hr>

## 📍 Overview

The v-wordpress-plugin-updater project is designed to streamline the management and updating of WordPress plugins and themes through a robust API and automated processes. It offers a comprehensive solution for secure plugin and theme updates, including user authentication, IP blacklisting, and detailed logging. The project provides an admin interface for managing updates, handling uploads, and monitoring logs, ensuring seamless operation across different environments. With support for both single and multisite installations, this project enhances WordPress site maintenance efficiency by automating update checks and installations, significantly reducing manual intervention.

---

## 🧩 Features

|    |   Feature         | Description |
|----|-------------------|---------------------------------------------------------------|
| ⚙️  | **Architecture**  | The project employs a modular architecture, dividing functionality into distinct components such as update APIs, configuration management, and admin interfaces. This ensures maintainability and scalability. |
| 🔩 | **Code Quality**  | The project adheres to coding standards, with clear organization of files and comprehensive inline comments. Security measures like input sanitization and IP blacklisting are integrated throughout the codebase. |
| 📄 | **Documentation** | Includes configuration and setup guides, inline comments, and function documentation. The repository seems well-organized, but additional user-facing documentation could enhance accessibility. |
| 🔌 | **Integrations**  | The project integrates with WordPress installations, leveraging external APIs for plugin and theme updates. Dependencies include authentication, WAF, and configuration libraries. |
| 🧩 | **Modularity**    | The codebase exhibits high modularity, with distinct folders for configuration, classes, and public access points. Each module handles specific functionality, promoting reusability and ease of maintenance. |
| ⚡️  | **Performance**   | The project is designed for efficiency, with secure download endpoints and optimized URL routing via `.htaccess`. However, explicit performance metrics and profiling data are not provided. |
| 🛡️ | **Security**      | Implements robust security measures including IP blacklisting, authentication libraries, and input validation. Admin interface security is enhanced through session management and a web application firewall (WAF). |
| 📦 | **Dependencies**  | The project relies on standard libraries like authentication, WAF, configuration management, IP blacklisting, and Dropzone for dynamic file handling. |
| 🚀 | **Scalability**   | Designed to handle increasing traffic with modular components for updates and management. However, explicit load testing data to back scalability claims is not available. |

---

## 🗂️ Repository Structure

```sh
└── v-wordpress-plugin-updater/
    ├── LICENSE
    ├── README.md
    ├── mu-plugin
    │   ├── v-sys-plugin-updater-mu.php
    │   ├── v-sys-plugin-updater.php
    │   └── v-sys-theme-updater.php
    ├── update-api
    │   ├── HOSTS
    │   ├── classes
    │   ├── config.php
    │   ├── lib
    │   ├── public
    │   ├── storage
    │   └── views
    └── v-wordpress-plugin-updater.png
```

---

## 📦 Modules

<details closed><summary>update-api</summary>

| File                                | Summary                                                                                                                                                                                                                                                                                                                              |
| ---                                 | ---                                                                                                                                                                                                                                                                                                                                  |
| [HOSTS](update-api/HOSTS)           | Define the configuration details and settings for server hosts involved in the update process, facilitating seamless communication and coordination for updating WordPress plugins and themes. This enhances the repositorys overall capability to manage updates effectively across different environments.                         |
| [config.php](update-api/config.php) | Configuration file for defining essential constants crucial for the plugin and theme update management system. Establishes authentication credentials, sets directory paths for plugins, themes, blacklists, and logs, thereby ensuring the smooth operation and organization of the update API within the repositorys architecture. |

</details>

<details closed><summary>update-api.public</summary>

| File                                       | Summary                                                                                                                                                                                                                                                                                                                                                            |
| ---                                        | ---                                                                                                                                                                                                                                                                                                                                                                |
| [index.php](update-api/public/index.php)   | Serves as the main entry point for the Update APIs web interface, providing a dashboard for managing WordPress hosts, plugins, themes, and viewing logs. Initializes sessions and includes necessary configurations and libraries, facilitating an admin interface with essential resources for a responsive and interactive user experience.                      |
| [api.php](update-api/public/api.php)       | Unified API endpoint that handles both plugin and theme update requests, validating domains and keys while enforcing IP blacklist rules. Delivers update packages when newer versions are available. |
| [.htaccess](update-api/public/.htaccess)   | Enhances URL routing by managing redirects and internal rewrites, ensuring clear and organized access to key sections like home, plupdate, thupdate, and logs. This optimization streamlines external requests and maintains seamless internal navigation within the update-api component of the repository.                                                       |
| [login.php](update-api/public/login.php)   | Provides an admin login interface for the Update API, enhancing security and access control. Integrates with configuration, authentication, and web application firewall libraries to facilitate validation and protection mechanisms within the broader WordPress plugin update ecosystem. Presents a user-friendly login form to manage API updates effectively. |
| [robots.txt](update-api/public/robots.txt) | Regulates web crawler access to the update-api directory with a specified delay, optimizing server load and ensuring the smooth operation of the plugin updater functionality within the repositorys architecture.                                                                                                                                                  |

</details>

<details closed><summary>update-api.public.themes</summary>

| File                                                  | Summary                                                                                                                                                                                                                                                                                                           |
</details>

<details closed><summary>update-api.storage</summary>

| File                                                | Summary                                                                                                                                                                                                                                                              |
| ---                                                 | ---                                                                                                                                                                                                                                                                  |
| [BLACKLIST.json](update-api/storage/BLACKLIST.json) | Maintains a list of blacklisted plugins or themes, preventing them from receiving updates via the update API. This ensures security and stability by blocking disallowed or potentially harmful software components within the WordPress plugin and theme ecosystem. |

</details>
<details closed><summary>update-api.classes</summary>

| File | Summary |
| --- | --- |
| [HomeHelper.php](update-api/classes/HomeHelper.php) | Manage HOSTS entries and handle form requests with sanitized input. |
| [PlHelper.php](update-api/classes/PlHelper.php) | Process plugin uploads and deletions securely. |
| [ThHelper.php](update-api/classes/ThHelper.php) | Process theme uploads and deletions securely. |
| [LogsHelper.php](update-api/classes/LogsHelper.php) | Group log entries by domain for easy review. |
| [ErrorHandler.php](update-api/classes/ErrorHandler.php) | Render session messages and errors in views. |
| [UtilityHandler.php](update-api/classes/UtilityHandler.php) | Validate input data and maintain the IP blacklist. |

</details>



<details closed><summary>update-api.app.pages</summary>

| File                                              | Summary                                                                                                                                                                                                                                                                                                                                                                       |
| ---                                               | ---                                                                                                                                                                                                                                                                                                                                                                           |
| [plupdate.php](update-api/views/plupdate.php) | Facilitate plugin uploads and manage update statuses through a user interface integrated with Dropzone for drag-and-drop functionality. Streamline plugin management by displaying existing plugins and handling file uploads and errors dynamically, contributing to the overall flexibility and usability of the plugin updater system within the repositorys architecture. |
| [thupdate.php](update-api/views/thupdate.php) | Facilitates the management and uploading of WordPress themes, providing a user-friendly interface for theme uploads, displaying a table of available themes, and offering real-time upload status feedback through Dropzone integration for enhanced user experience.                                                                                                         |
| [logs.php](update-api/views/logs.php)         | Displays plugin and theme logs on the WordPress Update API interface, facilitating monitoring and troubleshooting within the updater architecture. Integrates dynamic content, enhancing the user experience by providing real-time log outputs for both plugins and themes.                                                                                                  |
| [home.php](update-api/views/home.php)         | Facilitates the management of allowed hosts for the WordPress Update API by displaying a current list and providing a form to add new entries. Integrates seamlessly into the update-api section, enhancing control over authorized domains within the repositorys architecture.                                                                                              |

</details>

<details closed><summary>update-api.lib</summary>

| File                                        | Summary                                                                                                                                                                                                                                                                                                                                                            |
| ---                                         | ---                                                                                                                                                                                                                                                                                                                                                                |
| [auth-lib.php](update-api/lib/auth-lib.php) | Facilitates user authentication within the WordPress Update API by managing login and logout operations, handling session security, and implementing measures against failed login attempts to enhance system security. Essential for safeguarding access to the update APIs functionalities and integrating seamlessly with the repositorys broader architecture. |
| [waf-lib.php](update-api/lib/waf-lib.php)   | Sanitizes and validates input data, checks for disallowed characters and patterns, updates login attempt records, and manages IP blacklists for security, contributing to the broader security framework of the Update API within the v-wordpress-plugin-updater repository.                                                                                       |
| [load-lib.php](update-api/lib/load-lib.php) | Serve as a security and routing mechanism, ensuring only authenticated users can access specific pages within the WordPress update API. It checks for blacklisted IPs, redirects unauthenticated users to the login page, and dynamically loads page-specific helper and main files if they exist.                                                          |

</details>

<details closed><summary>mu-plugin</summary>

| File                                                                     | Summary                                                                                                                                                                                                                                                                                                                                                   |
| ---                                                                      | ---                                                                                                                                                                                                                                                                                                                                                       |
| [v-sys-theme-updater.php](mu-plugin/v-sys-theme-updater.php)         | Automates the daily update checks for WordPress themes by scheduling events, retrieving update details from a specified API, downloading, and installing theme updates seamlessly, ensuring themes remain current. Integrates error logging to handle update failures and provides feedback on the update status for each theme.                          |
| [v-sys-plugin-updater.php](mu-plugin/v-sys-plugin-updater.php)       | Facilitates automated plugin updates in a WordPress environment by scheduling daily checks and downloading new versions if available, ensuring plugins remain current and secure with minimal manual intervention. Integrates with the Vontainment API to verify and obtain updates, enhancing overall site maintenance efficiency.                       |
| [v-sys-plugin-updater-mu.php](mu-plugin/v-sys-plugin-updater-mu.php) | WP Plugin Updater Multisite automates daily checks and updates for WordPress plugins within a multisite environment, ensuring all plugins remain current by interacting with the Vontainment API to fetch updates, download, and install them seamlessly.                                                                                                 |

</details>

---

## 🚀 Getting Started
**System Requirements:**

* **PHP**: version 7.4 or higher
* **Web Server**: Apache, Nginx or any server capable of running PHP
* **Write Permissions**: ensure the web server can write to `update-api/storage`

### ⚙️ Installation

1. Clone or download this repository inside your web server document root.
2. Create the following directories so the Update API can store packages and logs:

   ```sh
   mkdir -p update-api/storage/plugins
   mkdir -p update-api/storage/themes
   mkdir -p update-api/storage/logs
   ```
3. Edit `update-api/config.php` and set the login credentials and directory constants. Adjust `VALID_USERNAME`, `VALID_PASSWORD`, and paths under `BASE_DIR` if the defaults do not match your setup.
4. Define the API constants used by the mu-plugins in your WordPress `wp-config.php`:

   ```php
   define('VONTMENT_KEY', 'your-api-key');
   define('VONTMENT_PLUGINS', 'https://example.com/update-api/public/api.php');
   define('VONTMENT_THEMES', 'https://example.com/update-api/public/api.php');
   ```
5. Ensure the web server user owns the `update-api/storage` directory so uploads and logs can be written.

### 🤖 Usage

1. Copy the files from the repository's `mu-plugin/` folder into your WordPress installation's `wp-content/mu-plugins/` directory. Create the directory if it doesn't exist. WordPress automatically loads any PHP files placed here.
2. Log in to the Update API via `update-api/public/login.php` using the credentials configured in `config.php` to manage hosts, plugins and themes.

## 🎗 License

This project is licensed under the [MIT License](LICENSE).

---
