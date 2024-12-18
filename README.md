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
| 🧩 | **Modularity**    | The codebase exhibits high modularity, with distinct folders for configuration, helpers, forms, and public access points. Each module handles specific functionality, promoting reusability and ease of maintenance. |
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
    │   ├── von-sys-plugin-updater-mu.php
    │   ├── von-sys-plugin-updater.php
    │   ├── von-sys-theme-updater-mu.php
    │   └── von-sys-theme-updater.php
    ├── png_20230308_211110_0000.png
    ├── screenshot.jpg
    └── update-api
        ├── HOSTS
        ├── app
        ├── config.php
        ├── lib
        ├── public
        └── storage
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
| [.htaccess](update-api/public/.htaccess)   | Enhances URL routing by managing redirects and internal rewrites, ensuring clear and organized access to key sections like home, plupdate, thupdate, and logs. This optimization streamlines external requests and maintains seamless internal navigation within the update-api component of the repository.                                                       |
| [login.php](update-api/public/login.php)   | Provides an admin login interface for the Update API, enhancing security and access control. Integrates with configuration, authentication, and web application firewall libraries to facilitate validation and protection mechanisms within the broader WordPress plugin update ecosystem. Presents a user-friendly login form to manage API updates effectively. |
| [robots.txt](update-api/public/robots.txt) | Regulate web crawler access to the update-api directory with a specified delay, optimizing server load and ensuring the smooth operation of the plugin updater functionality within the repositorys architecture.                                                                                                                                                  |

</details>

<details closed><summary>update-api.public.themes</summary>

| File                                                  | Summary                                                                                                                                                                                                                                                                                                           |
| ---                                                   | ---                                                                                                                                                                                                                                                                                                               |
| [api.php](update-api/public/themes/api.php)           | Serve as an endpoint for managing theme updates, verifying domain and key authenticity, and delivering updated theme versions to authorized users. Implements IP blacklisting and logs activity to enhance security and traceability, ensuring only authenticated requests can access and download theme updates. |
| [download.php](update-api/public/themes/download.php) | Provides a secure endpoint for downloading theme updates by validating user credentials and domain against an authorized list, ensuring only legitimate users can access the requested files while blocking blacklisted IPs.                                                                                      |

</details>

<details closed><summary>update-api.public.plugins</summary>

| File                                                   | Summary                                                                                                                                                                                                                                                                                                                                       |
| ---                                                    | ---                                                                                                                                                                                                                                                                                                                                           |
| [api.php](update-api/public/plugins/api.php)           | Provides an API for managing WordPress plugin updates, verifying access through domain and key checks, validating IP addresses, and delivering new plugin versions when available. Enhances security with IP blacklisting and logs both successful updates and unauthorized access attempts.                                                  |
| [download.php](update-api/public/plugins/download.php) | Facilitates secure plugin downloads by validating user credentials against a predefined list. Prevents unauthorized access and ensures only authorized users can download specified files. Integrates security measures like input sanitization and IP blacklisting to safeguard the update process within the WordPress plugin architecture. |

</details>

<details closed><summary>update-api.storage</summary>

| File                                                | Summary                                                                                                                                                                                                                                                              |
| ---                                                 | ---                                                                                                                                                                                                                                                                  |
| [BLACKLIST.json](update-api/storage/BLACKLIST.json) | Maintains a list of blacklisted plugins or themes, preventing them from receiving updates via the update API. This ensures security and stability by blocking disallowed or potentially harmful software components within the WordPress plugin and theme ecosystem. |

</details>

<details closed><summary>update-api.app.forms</summary>

| File                                                          | Summary                                                                                                                                                                                                                                                                                                                       |
| ---                                                           | ---                                                                                                                                                                                                                                                                                                                           |
| [home-forms.php](update-api/app/forms/home-forms.php)         | Manage entries in the HOSTS file by adding, updating, or deleting domains and keys based on POST requests, enhancing the functionality of the Update API within the WordPress plugin ecosystem. This integration ensures seamless updating and administration of plugin and theme assets.                                     |
| [thupdate-forms.php](update-api/app/forms/thupdate-forms.php) | Facilitate WordPress theme updates by enabling file uploads, deletions, and downloads. Integrates seamlessly with the broader update-api module to ensure easy management of theme files, supporting the repositorys functionality for maintaining up-to-date WordPress themes within a multi-functional plugin architecture. |
| [plupdate-forms.php](update-api/app/forms/plupdate-forms.php) | Enables management of WordPress plugins via an update API, offering functionality for uploading, deleting, and downloading plugin files. Supports validation of file extensions and safeguards against errors, ensuring smooth file handling and operational integrity within the broader repository architecture.            |

</details>

<details closed><summary>update-api.app.helpers</summary>

| File                                                              | Summary                                                                                                                                                                                                                                                                                                                                        |
| ---                                                               | ---                                                                                                                                                                                                                                                                                                                                            |
| [plupdate-helper.php](update-api/app/helpers/plupdate-helper.php) | Generate an HTML table displaying available WordPress plugin ZIP files, allowing users to delete plugins via a form submission. Divide the plugins list into two columns for better readability and user experience within the Update API section of the repository.                                                                           |
| [home-helper.php](update-api/app/helpers/home-helper.php)         | Generate and manage an HTML table displaying domain and key entries from the HOSTS file, supporting update and delete actions. Enhance the user interface of the WordPress Update API by organizing entries into columns for ease of use and better readability.                                                                               |
| [thupdate-helper.php](update-api/app/helpers/thupdate-helper.php) | Highlighting the themes available for updates and providing functionality to delete them, the thupdate-helper.php file within the update-api/app/helpers directory enhances the WordPress Update APIs capability to manage and display themes dynamically via HTML tables, seamlessly integrating with the parent plugins update architecture. |
| [logs-helper.php](update-api/app/helpers/logs-helper.php)         | Processes log files to group log entries by domain name and generates formatted HTML output displaying the status of each entry, aiding in simplified log analysis and monitoring for the WordPress Update API system. Integrates within the broader architecture to offer transparency and error tracking for plugin and theme updates.       |

</details>

<details closed><summary>update-api.app.pages</summary>

| File                                              | Summary                                                                                                                                                                                                                                                                                                                                                                       |
| ---                                               | ---                                                                                                                                                                                                                                                                                                                                                                           |
| [plupdate.php](update-api/app/pages/plupdate.php) | Facilitate plugin uploads and manage update statuses through a user interface integrated with Dropzone for drag-and-drop functionality. Streamline plugin management by displaying existing plugins and handling file uploads and errors dynamically, contributing to the overall flexibility and usability of the plugin updater system within the repositorys architecture. |
| [thupdate.php](update-api/app/pages/thupdate.php) | Facilitates the management and uploading of WordPress themes, providing a user-friendly interface for theme uploads, displaying a table of available themes, and offering real-time upload status feedback through Dropzone integration for enhanced user experience.                                                                                                         |
| [logs.php](update-api/app/pages/logs.php)         | Displays plugin and theme logs on the WordPress Update API interface, facilitating monitoring and troubleshooting within the updater architecture. Integrates dynamic content, enhancing the user experience by providing real-time log outputs for both plugins and themes.                                                                                                  |
| [home.php](update-api/app/pages/home.php)         | Facilitates the management of allowed hosts for the WordPress Update API by displaying a current list and providing a form to add new entries. Integrates seamlessly into the update-api section, enhancing control over authorized domains within the repositorys architecture.                                                                                              |

</details>

<details closed><summary>update-api.lib</summary>

| File                                        | Summary                                                                                                                                                                                                                                                                                                                                                            |
| ---                                         | ---                                                                                                                                                                                                                                                                                                                                                                |
| [auth-lib.php](update-api/lib/auth-lib.php) | Facilitates user authentication within the WordPress Update API by managing login and logout operations, handling session security, and implementing measures against failed login attempts to enhance system security. Essential for safeguarding access to the update APIs functionalities and integrating seamlessly with the repositorys broader architecture. |
| [waf-lib.php](update-api/lib/waf-lib.php)   | Sanitizes and validates input data, checks for disallowed characters and patterns, updates login attempt records, and manages IP blacklists for security, contributing to the broader security framework of the Update API within the v-wordpress-plugin-updater repository.                                                                                       |
| [load-lib.php](update-api/lib/load-lib.php) | Serve as a security and routing mechanism, ensuring only authenticated users can access specific pages within the WordPress update API. It checks for blacklisted IPs, redirects unauthenticated users to the login page, and dynamically loads page-specific helper, form, and main files if they exist.                                                          |

</details>

<details closed><summary>mu-plugin</summary>

| File                                                                     | Summary                                                                                                                                                                                                                                                                                                                                                   |
| ---                                                                      | ---                                                                                                                                                                                                                                                                                                                                                       |
| [von-sys-theme-updater.php](mu-plugin/von-sys-theme-updater.php)         | Automates the daily update checks for WordPress themes by scheduling events, retrieving update details from a specified API, downloading, and installing theme updates seamlessly, ensuring themes remain current. Integrates error logging to handle update failures and provides feedback on the update status for each theme.                          |
| [von-sys-plugin-updater.php](mu-plugin/von-sys-plugin-updater.php)       | Facilitates automated plugin updates in a WordPress environment by scheduling daily checks and downloading new versions if available, ensuring plugins remain current and secure with minimal manual intervention. Integrates with the Vontainment API to verify and obtain updates, enhancing overall site maintenance efficiency.                       |
| [von-sys-plugin-updater-mu.php](mu-plugin/von-sys-plugin-updater-mu.php) | WP Plugin Updater Multisite automates daily checks and updates for WordPress plugins within a multisite environment, ensuring all plugins remain current by interacting with the Vontainment API to fetch updates, download, and install them seamlessly.                                                                                                 |
| [von-sys-theme-updater-mu.php](mu-plugin/von-sys-theme-updater-mu.php)   | Automates daily WordPress theme updates across multisite installations. Handles scheduled update checks, verifies theme versions against Vontainment API, and manages theme package downloads and installations. Logs outcomes and errors to ensure smooth, repeated theme maintenance. Integrates seamlessly into the existing plugin updater framework. |

</details>

---

## 🚀 Getting Started

**System Requirements:**

* **PHP**: `version x.y.z`

### ⚙️ Installation

<h4>From <code>source</code></h4>

> 1. Clone the v-wordpress-plugin-updater repository:
>
> ```console
> $ git clone ../v-wordpress-plugin-updater
> ```
>
> 2. Change to the project directory:
> ```console
> $ cd v-wordpress-plugin-updater
> ```
>
> 3. Install the dependencies:
> ```console
> $ composer install
> ```

### 🤖 Usage

<h4>From <code>source</code></h4>

> Run v-wordpress-plugin-updater using the command below:
> ```console
> $ php main.php
> ```

---

## 🎗 License

This project is protected under the [MIT License](https://github.com/djav1985/v-chatgpt-editor/blob/main/LICENSE) License.

---
