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

1. **Update API Server** (`update-api/`): A standalone PHP web application that hosts and serves plugin/theme update packages. Built with a modern MVC architecture using FastRoute for routing, Doctrine DBAL for SQLite database management, and comprehensive security features including encrypted API keys, IP blacklisting, and session management.

2. **WordPress Client Plugin** (`v-wp-updater/`): A WordPress plugin that automatically checks for and installs updates from the API server. It integrates seamlessly with WordPress core update mechanisms, providing automated daily update checks, REST API endpoints for remote management, and comprehensive logging.

This architecture enables centralized control over plugin and theme updates across multiple WordPress installations, reducing manual maintenance overhead while maintaining security and reliability. The system supports both single-site and multisite WordPress installations and provides detailed logging and monitoring capabilities through an intuitive web-based admin interface.

---

## Features

|      | Component            | Details                                                                                     |
| :--- | :------------------- | :------------------------------------------------------------------------------------------ |
| ⚙️  | **Architecture**     | <ul><li>Dual-component system: standalone Update API server + WordPress client plugin</li><li>MVC architecture with FastRoute routing and Doctrine DBAL</li><li>Separate namespaces: `App\` (server) and `VWPU\` (client)</li></ul> |
| 🔩 | **Code Quality**     | <ul><li>PSR-12 coding standards for API server</li><li>WordPress Coding Standards for client plugin</li><li>PHPStan static analysis at level 6</li><li>Comprehensive PHPUnit test coverage</li></ul> |
| 📄 | **Documentation**    | <ul><li>Detailed README with installation and usage instructions</li><li>API specification with request/response examples</li><li>Inline PHPDoc comments throughout codebase</li></ul> |
| 🔌 | **Integrations**      | <ul><li>WordPress hooks and filters integration</li><li>REST API endpoints for remote management</li><li>Cron-based synchronization between filesystem and database</li></ul> |
| 🧩 | **Modularity**        | <ul><li>Separate controllers for API, login, hosts, plugins, themes, and logs</li><li>Helper classes for encryption, validation, and message handling</li><li>Model layer for database operations (plugins, themes, hosts, logs, blacklist)</li></ul> |
| 🧪 | **Testing**           | <ul><li>PHPUnit test suite for both components</li><li>Tests for routing, database, session management, and updater logic</li><li>Namespace-based mocking for isolated unit tests</li></ul> |
| ⚡️  | **Performance**       | <ul><li>SQLite database for efficient metadata storage</li><li>Asynchronous update processing per plugin/theme</li><li>Background worker mode for cron synchronization</li></ul> |
| 🛡️ | **Security**          | <ul><li>Encrypted API keys using AES-256</li><li>IP-based blacklisting after failed login attempts</li><li>Session timeout and user agent validation</li><li>CSRF protection on all forms</li><li>Input validation and sanitization</li></ul> |
| 📦 | **Dependencies**      | <ul><li>PHP 7.4+ with SQLite support</li><li>Composer packages: FastRoute, Doctrine DBAL</li><li>WordPress core functions for client plugin</li><li>Web server with PHP support (Apache/Nginx)</li></ul> |

---

## Project Structure

```sh
└── v-wordpress-plugin-updater/
    ├── .github
    │   └── copilot-instructions.md
    ├── LICENSE
    ├── README.md
    ├── v-wp-updater                        # WordPress client plugin
    │   ├── api
    │   │   ├── API_SCHEMA.md
    │   │   ├── DebugLogApi.php
    │   │   ├── PluginApi.php
    │   │   └── ThemeApi.php
    │   ├── helpers
    │   │   ├── AbstractRemoteUpdater.php
    │   │   ├── Logger.php
    │   │   ├── Options.php
    │   │   └── SilentUpgraderSkin.php
    │   ├── services
    │   │   ├── PluginUpdater.php
    │   │   └── ThemeUpdater.php
    │   ├── widgets
    │   │   └── settings.php
    │   ├── install.php
    │   ├── uninstall.php
    │   └── v-wp-updater.php               # Main plugin file
    ├── update-api                          # Update API server
    │   ├── app
    │   │   ├── Controllers
    │   │   │   ├── ApiController.php
    │   │   │   ├── HomeController.php
    │   │   │   ├── LoginController.php
    │   │   │   ├── LogsController.php
    │   │   │   ├── PluginsController.php
    │   │   │   ├── SiteLogsController.php
    │   │   │   └── ThemesController.php
    │   │   ├── Core
    │   │   │   ├── Controller.php
    │   │   │   ├── Csrf.php
    │   │   │   ├── DatabaseManager.php
    │   │   │   ├── ErrorManager.php
    │   │   │   ├── Response.php
    │   │   │   ├── Router.php
    │   │   │   └── SessionManager.php
    │   │   ├── Helpers
    │   │   │   ├── CronWorker.php
    │   │   │   ├── Encryption.php
    │   │   │   ├── MessageHelper.php
    │   │   │   ├── Validation.php
    │   │   │   └── WorkerHelper.php
    │   │   ├── Models
    │   │   │   ├── Blacklist.php
    │   │   │   ├── HostsModel.php
    │   │   │   ├── LogModel.php
    │   │   │   ├── PluginModel.php
    │   │   │   └── ThemeModel.php
    │   │   └── Views
    │   │       ├── 404.php
    │   │       ├── home.php
    │   │       ├── layouts
    │   │       │   ├── footer.php
    │   │       │   └── header.php
    │   │       ├── login.php
    │   │       ├── logs.php
    │   │       ├── plupdate.php
    │   │       ├── sitelogs.php
    │   │       └── thupdate.php
    │   ├── composer.json
    │   ├── config.php
    │   ├── cron.php
    │   ├── php.ini
    │   ├── public
    │   │   ├── .htaccess
    │   │   ├── assets
    │   │   │   ├── css
    │   │   │   │   ├── login.css
    │   │   │   │   ├── mobile.css
    │   │   │   │   └── styles.css
    │   │   │   ├── images
    │   │   │   │   ├── background.png
    │   │   │   │   ├── login.jpg
    │   │   │   │   └── logo.png
    │   │   │   └── js
    │   │   │       ├── footer-scripts.js
    │   │   │       └── header-scripts.js
    │   │   ├── favicon.ico
    │   │   ├── index.php
    │   │   ├── install.php
    │   │   └── robots.txt
    │   └── storage
    │       ├── logs
    │       ├── plugins
    │       ├── themes
    │       └── updater.sqlite


<details open>
	<summary><b><code>V-WORDPRESS-PLUGIN-UPDATER/</code></b></summary>
	<!-- __root__ Submodule -->
	<details>
		<blockquote>
			<div class='directory-path' style='padding: 8px 0; color: #666;'>
				<code><b>⦿ __root__</b></code>
			<table style='width: 100%; border-collapse: collapse;'>
                                                          <thead>
				<tr style='background-color: #f8f9fa;'>
					<th style='width: 30%; text-align: left; padding: 8px;'>File Name</th>
					<th style='text-align: left; padding: 8px;'>Summary</th>
				</tr>
			</thead>
				<tr style='border-bottom: 1px solid #eee;'>
					<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/LICENSE'>LICENSE</a></b></td>
					<td style='padding: 8px;'>- Provides the licensing terms for the project, establishing legal permissions and restrictions for software use, distribution, and modification within the overall architecture<br>- Ensures clarity on rights granted to users and contributors, supporting open-source collaboration and legal compliance across the entire codebase.</td>
				</tr>
			</table>
		</blockquote>
	</details>
	<!-- update-api Submodule -->
	<details>
		<summary><b>update-api</b></summary>
		<blockquote>
			<div class='directory-path' style='padding: 8px 0; color: #666;'>
				<code><b>⦿ update-api</b></code>
			<table style='width: 100%; border-collapse: collapse;'>
			<thead>
				<tr style='background-color: #f8f9fa;'>
					<th style='width: 30%; text-align: left; padding: 8px;'>File Name</th>
					<th style='text-align: left; padding: 8px;'>Summary</th>
				</tr>
			</thead>
				<tr style='border-bottom: 1px solid #eee;'>
					<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/php.ini'>php.ini</a></b></td>
					<td style='padding: 8px;'>- Configure PHP environment settings to optimize API performance and stability within the update API module<br>- By managing error display, upload limits, and execution times, it ensures reliable handling of data uploads and processing tasks, supporting the overall architectures goal of maintaining a robust and efficient API service.</td>
				</tr>
				<tr style='border-bottom: 1px solid #eee;'>
					<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/config.php'>config.php</a></b></td>
					<td style='padding: 8px;'>- Defines core configuration constants for the WordPress Update API, establishing authentication parameters, directory paths, and session management settings<br>- These configurations facilitate secure and organized access to plugin, theme, and log storage, supporting the API’s role in managing and delivering updates within the overall project architecture.</td>
				</tr>
				<tr style='border-bottom: 1px solid #eee;'>
					<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/cron.php'>cron.php</a></b></td>
					<td style='padding: 8px;'>- Synchronizes plugin and theme ZIP files from the filesystem to the SQLite database, maintaining metadata for version tracking and updates<br>- Supports background worker mode for scheduled execution, ensuring the database remains current with available update packages<br>- Also manages cleanup of expired IP blacklist entries.</td>
				</tr>
				<tr style='border-bottom: 1px solid #eee;'>
			<!-- public Submodule -->
			<details>
				<summary><b>public</b></summary>
				<blockquote>
					<div class='directory-path' style='padding: 8px 0; color: #666;'>
						<code><b>⦿ update-api.public</b></code>
					<table style='width: 100%; border-collapse: collapse;'>
					<thead>
						<tr style='background-color: #f8f9fa;'>
							<th style='width: 30%; text-align: left; padding: 8px;'>File Name</th>
							<th style='text-align: left; padding: 8px;'>Summary</th>
						</tr>
					</thead>
						<tr style='border-bottom: 1px solid #eee;'>
							<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/public/robots.txt'>robots.txt</a></b></td>
							<td style='padding: 8px;'>- Defines web crawler access restrictions by disallowing all user agents from indexing the site, ensuring the entire website remains private and excluded from search engine results<br>- This configuration supports the overall architecture by controlling visibility and maintaining confidentiality of the sites content.</td>
						</tr>
						<tr style='border-bottom: 1px solid #eee;'>
							<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/public/index.php'>index.php</a></b></td>
							<td style='padding: 8px;'>- Facilitates routing and error handling for the WordPress Update API, enabling seamless request dispatching and robust middleware management<br>- Serves as the entry point that initializes session management, loads configuration, and directs incoming API requests to appropriate handlers, ensuring reliable operation within the overall project architecture.</td>
						</tr>
						<tr style='border-bottom: 1px solid #eee;'>
							<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/public/.htaccess'>.htaccess</a></b></td>
							<td style='padding: 8px;'>- Defines URL rewriting rules to route requests to the main application handler, ensuring proper request processing<br>- Implements caching policies for static assets to optimize load times and reduce server load<br>- Enhances performance and efficiency across the web application by managing request flow and client-side caching strategies.</td>
						</tr>
					</table>
				</blockquote>
			</details>
			<!-- app Submodule -->
			<details>
				<summary><b>app</b></summary>
				<blockquote>
					<div class='directory-path' style='padding: 8px 0; color: #666;'>
						<code><b>⦿ update-api.app</b></code>
					<!-- Models Submodule -->
					<details>
						<summary><b>Models</b></summary>
						<blockquote>
							<div class='directory-path' style='padding: 8px 0; color: #666;'>
								<code><b>⦿ update-api.app.Models</b></code>
							<table style='width: 100%; border-collapse: collapse;'>
							<thead>
								<tr style='background-color: #f8f9fa;'>
									<th style='width: 30%; text-align: left; padding: 8px;'>File Name</th>
									<th style='text-align: left; padding: 8px;'>Summary</th>
								</tr>
                                                          </thead>
                                                                  <tr style='border-bottom: 1px solid #eee;'>
                                                                          <td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/app/Models/Blacklist.php'>Blacklist.php</a></b></td>
                                                                          <td style='padding: 8px;'>- Tracks failed login attempts and manages IP blacklisting for the Update API, automatically expiring bans after a defined period to maintain security.</td>
                                                                  </tr>
                                                                  <tr style='border-bottom: 1px solid #eee;'>
                                                                          <td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/app/Models/ThemeModel.php'>ThemeModel.php</a></b></td>
                                                                          <td style='padding: 8px;'>- Manages theme files within the WordPress Update API, enabling retrieval, deletion, and uploading of theme ZIP packages<br>- Facilitates theme lifecycle operations, ensuring proper file handling, validation, and size restrictions to support seamless theme management in the broader update infrastructure.</td>
                                                                  </tr>
								<tr style='border-bottom: 1px solid #eee;'>
									<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/app/Models/HostsModel.php'>HostsModel.php</a></b></td>
									<td style='padding: 8px;'>- Manages host entries within the WordPress Update API by providing functionalities to retrieve, add, update, and delete host records<br>- Ensures consistent handling of host data, maintains log integrity, and supports dynamic configuration of host access controls, integral to the overall architecture for secure and flexible update management.</td>
								</tr>
								<tr style='border-bottom: 1px solid #eee;'>
									<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/app/Models/PluginModel.php'>PluginModel.php</a></b></td>
									<td style='padding: 8px;'>- Manages WordPress plugin files within the UpdateAPI architecture by providing functionalities for retrieving, uploading, and deleting plugin ZIP files<br>- Ensures proper handling of file validation, size constraints, and safe file operations, supporting seamless plugin management and updates in the broader system<br>- Facilitates efficient plugin lifecycle control aligned with the APIs update and deployment processes.</td>
								</tr>
								<tr style='border-bottom: 1px solid #eee;'>
									<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/app/Models/LogModel.php'>LogModel.php</a></b></td>
									<td style='padding: 8px;'>- Provides functionality to process and visualize log data related to WordPress updates, grouping entries by domain and status<br>- It enhances the overall architecture by enabling clear, styled reporting of update success or failure, including historical context, which supports monitoring and troubleshooting within the update management system.</td>
								</tr>
							</table>
						</blockquote>
					</details>
					<!-- Core Submodule -->
					<details>
						<summary><b>Core</b></summary>
						<blockquote>
							<div class='directory-path' style='padding: 8px 0; color: #666;'>
								<code><b>⦿ update-api.app.Core</b></code>
							<table style='width: 100%; border-collapse: collapse;'>
							<thead>
								<tr style='background-color: #f8f9fa;'>
									<th style='width: 30%; text-align: left; padding: 8px;'>File Name</th>
									<th style='text-align: left; padding: 8px;'>Summary</th>
								</tr>
							</thead>
								<tr style='border-bottom: 1px solid #eee;'>
									<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/app/Core/Router.php'>Router.php</a></b></td>
									<td style='padding: 8px;'>- Defines the core routing mechanism for the WordPress Update API, directing incoming requests to appropriate controllers based on URL paths<br>- Ensures authentication for protected routes and handles URL redirection and error responses, facilitating seamless request handling within the applications architecture.</td>
								</tr>
								<tr style='border-bottom: 1px solid #eee;'>
									<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/app/Core/Controller.php'>Controller.php</a></b></td>
									<td style='padding: 8px;'>- Provides a foundational class for rendering view templates within the WordPress Update API, facilitating separation of concerns by managing presentation logic<br>- It supports the overall architecture by enabling consistent and streamlined output generation, ensuring that different parts of the application can display data effectively while maintaining a clean code structure.</td>
								</tr>
								<tr style='border-bottom: 1px solid #eee;'>
                                                                        <td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/app/Core/ErrorManager.php'>ErrorManager.php</a></b></td>
                                                                        <td style='padding: 8px;'>- Provides centralized error handling and logging for the WordPress Update API through a singleton manager that registers handlers for runtime errors, exceptions, and shutdown events<br>- Facilitates graceful error responses, maintains application stability, and logs critical issues, thereby supporting reliable API operations and easier troubleshooting within the overall system architecture.</td>
								</tr>
								<tr style='border-bottom: 1px solid #eee;'>
                                                                   <td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/app/Core/SessionManager.php'>SessionManager.php</a></b></td>
                                                                       <td style='padding: 8px;'>- Implements authentication and security checks within the WordPress Update API, ensuring only authorized users access update functionalities<br>- It enforces session validation by checking timeout and user agent consistency, while IP blacklist enforcement occurs during authentication to block banned addresses, maintaining secure and controlled API interactions as part of the overall application security architecture.</td>
                                                               </tr>
                                                        </table>
                                                </blockquote>
                                        </details>
					<!-- Views Submodule -->
					<details>
						<summary><b>Views</b></summary>
						<blockquote>
							<div class='directory-path' style='padding: 8px 0; color: #666;'>
								<code><b>⦿ update-api.app.Views</b></code>
							<table style='width: 100%; border-collapse: collapse;'>
							<thead>
								<tr style='background-color: #f8f9fa;'>
									<th style='width: 30%; text-align: left; padding: 8px;'>File Name</th>
									<th style='text-align: left; padding: 8px;'>Summary</th>
								</tr>
							</thead>
								<tr style='border-bottom: 1px solid #eee;'>
									<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/app/Views/thupdate.php'>thupdate.php</a></b></td>
									<td style='padding: 8px;'>- Provides a user interface for managing WordPress themes within the UpdateAPI platform, enabling viewing, uploading, and status tracking of theme packages<br>- Integrates a dynamic upload mechanism with real-time feedback, supporting seamless theme updates and extensions through a structured, web-based dashboard aligned with the overall API architecture.</td>
								</tr>
								<tr style='border-bottom: 1px solid #eee;'>
									<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/app/Views/login.php'>login.php</a></b></td>
									<td style='padding: 8px;'>- Provides the login interface for the WordPress Update API, enabling administrators to authenticate securely before accessing update management functionalities<br>- Integrates styling and scripts to ensure a user-friendly experience, while facilitating session handling and error messaging within the broader API architecture<br>- Serves as the entry point for authorized users to interact with the update management system.</td>
								</tr>
								<tr style='border-bottom: 1px solid #eee;'>
									<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/app/Views/404.php'>404.php</a></b></td>
									<td style='padding: 8px;'>- Provides a user-friendly 404 error page for the UpdateAPI, ensuring clear communication when a requested resource is not found<br>- Integrates consistent styling and scripts within the broader WordPress-based API architecture, maintaining a cohesive user experience and guiding users appropriately within the APIs web interface.</td>
								</tr>
								<tr style='border-bottom: 1px solid #eee;'>
									<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/app/Views/logs.php'>logs.php</a></b></td>
									<td style='padding: 8px;'>- Displays plugin and theme update logs within the WordPress Update API, providing a clear interface for monitoring recent changes<br>- Integrates header and footer layouts to maintain consistent styling across the application, facilitating efficient tracking of update activities and supporting overall system transparency and troubleshooting.</td>
								</tr>
								<tr style='border-bottom: 1px solid #eee;'>
									<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/app/Views/plupdate.php'>plupdate.php</a></b></td>
									<td style='padding: 8px;'>- Provides a user interface for managing WordPress plugin updates within the UpdateAPI platform<br>- Facilitates viewing installed plugins, uploading new plugin ZIP files via drag-and-drop, and displaying real-time upload status messages<br>- Integrates with backend processes to streamline plugin management, ensuring seamless updates and installations in a structured, user-friendly manner.</td>
								</tr>
                                                                <tr style='border-bottom: 1px solid #eee;'>
                                                                        <td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/app/Views/home.php'>home.php</a></b></td>
                                                                        <td style='padding: 8px;'>- Provides a user interface for managing allowed hosts within the WordPress Update API, enabling viewing and adding domain entries<br>- Facilitates administrative control over host configurations, ensuring secure and organized management of permitted domains for update operations<br>- Integrates form handling and display logic to support dynamic updates in the APIs host list.</td>
                                                                </tr>
                                                                <tr style='border-bottom: 1px solid #eee;'>
                                                                        <td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/app/Views/sitelogs.php'>sitelogs.php</a></b></td>
                                                                        <td style='padding: 8px;'>- Provides a site-specific log viewer interface for monitoring update activities per WordPress site<br>- Enables filtering and viewing of update logs by domain, facilitating troubleshooting and tracking of plugin and theme update operations across multiple WordPress installations managed by the Update API.</td>
                                                                </tr>
                                                        </table>
							<!-- layouts Submodule -->
							<details>
								<summary><b>layouts</b></summary>
								<blockquote>
									<div class='directory-path' style='padding: 8px 0; color: #666;'>
										<code><b>⦿ update-api.app.Views.layouts</b></code>
									<table style='width: 100%; border-collapse: collapse;'>
									<thead>
										<tr style='background-color: #f8f9fa;'>
											<th style='width: 30%; text-align: left; padding: 8px;'>File Name</th>
											<th style='text-align: left; padding: 8px;'>Summary</th>
										</tr>
									</thead>
										<tr style='border-bottom: 1px solid #eee;'>
											<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/app/Views/layouts/header.php'>header.php</a></b></td>
											<td style='padding: 8px;'>- Defines the header layout for the WordPress Update API admin interface, establishing the page structure, navigation, and styling<br>- It facilitates seamless user interaction by providing consistent branding, navigation tabs for managing hosts, plugins, themes, and viewing logs, and integrates necessary scripts and styles to support the APIs administrative functions within the overall architecture.</td>
										</tr>
										<tr style='border-bottom: 1px solid #eee;'>
											<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/app/Views/layouts/footer.php'>footer.php</a></b></td>
											<td style='padding: 8px;'>- Defines the footer layout for the UpdateAPIs web interface, providing consistent branding and user interface closure across pages<br>- It includes dynamic copyright information, links to assets and scripts, and integrates error message handling to ensure seamless user experience within the overall WordPress-based architecture.</td>
										</tr>
									</table>
								</blockquote>
							</details>
						</blockquote>
					</details>
					<!-- Controllers Submodule -->
					<details>
						<summary><b>Controllers</b></summary>
						<blockquote>
							<div class='directory-path' style='padding: 8px 0; color: #666;'>
								<code><b>⦿ update-api.app.Controllers</b></code>
							<table style='width: 100%; border-collapse: collapse;'>
							<thead>
								<tr style='background-color: #f8f9fa;'>
									<th style='width: 30%; text-align: left; padding: 8px;'>File Name</th>
									<th style='text-align: left; padding: 8px;'>Summary</th>
								</tr>
							</thead>
								<tr style='border-bottom: 1px solid #eee;'>
									<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/app/Controllers/ApiController.php'>ApiController.php</a></b></td>
									<td style='padding: 8px;'>- Implements a WordPress Update API endpoint to securely handle update requests for plugins and themes<br>- Validates incoming parameters, authenticates domain keys, and serves the latest compatible files based on version comparisons<br>- Integrates logging and access control, ensuring only authorized requests retrieve updates, thereby maintaining the integrity and security of the update distribution process within the overall architecture.</td>
								</tr>
								<tr style='border-bottom: 1px solid #eee;'>
									<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/app/Controllers/LogsController.php'>LogsController.php</a></b></td>
									<td style='padding: 8px;'>- Provides an interface for retrieving and displaying log data related to plugin and theme activities within the WordPress Update API<br>- It orchestrates the processing of log files and renders a view to present log outputs, supporting monitoring and troubleshooting of plugin and theme updates in the overall application architecture.</td>
								</tr>
								<tr style='border-bottom: 1px solid #eee;'>
									<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/app/Controllers/PluginsController.php'>PluginsController.php</a></b></td>
									<td style='padding: 8px;'>- Manages plugin-related operations within the WordPress Update API, including uploading, deleting, and displaying plugins<br>- Facilitates secure handling of plugin files and user actions, generating dynamic HTML interfaces for plugin management<br>- Integrates with core models and middleware to ensure smooth, secure interactions, supporting the overall architecture of plugin administration in the update ecosystem.</td>
								</tr>
								<tr style='border-bottom: 1px solid #eee;'>
									<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/app/Controllers/HomeController.php'>HomeController.php</a></b></td>
									<td style='padding: 8px;'>- Manages user interactions for the WordPress Update API by handling host entries, including adding, updating, and deleting domains and keys<br>- Validates requests, maintains session messages, and renders the hosts management interface with dynamic HTML tables<br>- Integrates with the overall architecture to facilitate secure, user-driven configuration of host data within the update system.</td>
								</tr>
								<tr style='border-bottom: 1px solid #eee;'>
									<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/app/Controllers/ThemesController.php'>ThemesController.php</a></b></td>
									<td style='padding: 8px;'>- Manages theme-related operations within the WordPress Update API, including uploading, deleting, and displaying themes<br>- Facilitates secure handling of theme files through validation and CSRF protection, while generating dynamic HTML interfaces for theme management<br>- Integrates with core models and middleware to ensure smooth, secure interactions across the applications architecture.</td>
								</tr>
								<tr style='border-bottom: 1px solid #eee;'>
                                                                   <td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/app/Controllers/LoginController.php'>LoginController.php</a></b></td>
									<td style='padding: 8px;'>- Handles user authentication within the WordPress Update API, managing login sessions, validating credentials, and redirecting users appropriately<br>- Ensures secure session management, tracks failed login attempts, and integrates blacklisting for security<br>- Facilitates user access control, enabling authenticated interactions with the API while safeguarding against unauthorized access.</td>
								</tr>
								<tr style='border-bottom: 1px solid #eee;'>
									<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/app/Controllers/SiteLogsController.php'>SiteLogsController.php</a></b></td>
									<td style='padding: 8px;'>- Provides site-specific log viewing capabilities within the WordPress Update API, allowing administrators to filter and view update logs by domain<br>- Facilitates detailed monitoring and troubleshooting of plugin and theme updates for individual WordPress installations, supporting the overall logging and diagnostics architecture of the update management system.</td>
								</tr>
							</table>
						</blockquote>
					</details>
				</blockquote>
			</details>
			<!-- storage Submodule -->
			<details>
				<summary><b>storage</b></summary>
				<blockquote>
					<div class='directory-path' style='padding: 8px 0; color: #666;'>
						<code><b>⦿ update-api.storage</b></code>
					<table style='width: 100%; border-collapse: collapse;'>
					<thead>
						<tr style='background-color: #f8f9fa;'>
							<th style='width: 30%; text-align: left; padding: 8px;'>File Name</th>
							<th style='text-align: left; padding: 8px;'>Summary</th>
						</tr>
					</thead>
						<tr style='border-bottom: 1px solid #eee;'>
                                                        <td style='padding: 8px;'><b>updater.sqlite</b></td>
                                                        <td style='padding: 8px;'>- SQLite database storing plugin and theme metadata and tracking failed logins in the blacklist table.</td>
						</tr>
					</table>
				</blockquote>
			</details>
		</blockquote>
	</details>
	<!-- v-wp-updater Submodule -->
	<details>
		<summary><b>v-wp-updater</b></summary>
		<blockquote>
			<div class='directory-path' style='padding: 8px 0; color: #666;'>
				<code><b>⦿ v-wp-updater</b></code>
			<table style='width: 100%; border-collapse: collapse;'>
			<thead>
				<tr style='background-color: #f8f9fa;'>
					<th style='width: 30%; text-align: left; padding: 8px;'>File Name</th>
					<th style='text-align: left; padding: 8px;'>Summary</th>
				</tr>
			</thead>
				<tr style='border-bottom: 1px solid #eee;'>
					<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/v-wp-updater/v-wp-updater.php'>v-wp-updater.php</a></b></td>
					<td style='padding: 8px;'>- Main plugin file for the V WordPress Plugin Updater client<br>- Implements automated plugin and theme update checking and installation by connecting to the remote Update API server<br>- Integrates with WordPress hooks to schedule daily update checks, handles API authentication, and provides REST API endpoints for remote management and debugging.</td>
				</tr>
				<tr style='border-bottom: 1px solid #eee;'>
					<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/v-wp-updater/services/PluginUpdater.php'>PluginUpdater.php</a></b></td>
					<td style='padding: 8px;'>- Manages automated plugin update checks and installations for WordPress<br>- Retrieves available updates from the remote API, downloads update packages, and installs them using WordPress core upgrade mechanisms<br>- Provides comprehensive logging and error handling to ensure reliable plugin maintenance within the overall update management architecture.</td>
				</tr>
				<tr style='border-bottom: 1px solid #eee;'>
					<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/v-wp-updater/services/ThemeUpdater.php'>ThemeUpdater.php</a></b></td>
					<td style='padding: 8px;'>- Manages automated theme update checks and installations for WordPress<br>- Retrieves available theme updates from the remote API, downloads update packages, and installs them using WordPress core upgrade mechanisms<br>- Provides comprehensive logging and error handling to ensure reliable theme maintenance within the overall update management architecture.</td>
				</tr>
			</table>
		</blockquote>
	</details>
</details>

---

## Getting Started
**System Requirements:**

* **PHP**: version 7.4 or higher
* **Web Server**: Apache, Nginx or any server capable of running PHP
* **Write Permissions**: ensure the web server can write to `/storage`

### Installation

#### Update API Server Setup

1. Clone or download this repository to your web server.
2. Set `update-api/public/` as your web server document root.
3. Create the following directories so the Update API can store packages and logs:

   ```sh
   mkdir -p update-api/storage/plugins
   mkdir -p update-api/storage/themes
   mkdir -p update-api/storage/logs
   ```

4. Edit `update-api/config.php` and set the login credentials and directory constants. Adjust `VALID_USERNAME`, `VALID_PASSWORD_HASH` (generate with `password_hash()`), `LOG_FILE`, and paths under `BASE_DIR` if the defaults do not match your setup.

5. Set an `ENCRYPTION_KEY` environment variable used to secure host keys:

   ```sh
   export ENCRYPTION_KEY="your-32-byte-secret"
   ```

6. Ensure the web server user owns the `update-api/storage/` directory so uploads and logs can be written. Application logs are written to `LOG_FILE` (default `update-api/storage/logs/app.log`).

7. Navigate to `update-api/public/` and run `php install.php` in your browser or via CLI to create the SQLite database and required tables. Ensure `update-api/storage/updater.sqlite` is writable by the web server.

8. Configure a system cron to run the sync worker regularly:

   ```sh
   */15 * * * * cd /path/to/update-api && php cron.php --worker
   ```

   This keeps the database in sync with plugin and theme ZIP files in the storage directories.

#### WordPress Client Plugin Setup

1. Copy the `v-wp-updater/` directory to your WordPress installation's `wp-content/plugins/` directory.

2. Define the API URL in your WordPress `wp-config.php`:

   ```php
   define('VONTMNT_API_URL', 'https://updates.example.com/api');
   ```

3. Store the API key for your WordPress site. The updater uses the API key stored in the `vontmnt_api_key` option. Set this via provisioning or use the WordPress admin panel:

   ```php
   update_option('vontmnt_api_key', 'your-api-key-from-server');
   ```

4. Activate the plugin through the WordPress admin panel or WP-CLI.

5. The plugin will automatically schedule daily update checks for plugins and themes.

**Note:** When a host entry is created or its key regenerated in the Update API admin panel, update the client installation with the new key using your provisioning process.

### Usage

#### Managing the Update API Server

1. Log in to the Update API admin panel by visiting `https://your-update-server.com/login` using the credentials configured in `update-api/config.php`.

2. **Manage Hosts**: Add authorized WordPress domains and generate API keys in the `/home` route.

3. **Upload Plugins**: Navigate to `/plupdate` to upload plugin ZIP files. Files must be named following the pattern `{slug}_{version}.zip` (e.g., `my-plugin_1.2.3.zip`).

4. **Upload Themes**: Navigate to `/thupdate` to upload theme ZIP files. Files must follow the same naming pattern as plugins.

5. **View Logs**: Check `/logs` for general update activity logs, or `/sitelogs` to view logs filtered by specific WordPress sites.

6. The cron worker will automatically sync uploaded files to the database. Ensure the cron job is configured as described in the installation steps.

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
| `400 Bad Request` | Missing required parameters |
| `403 Forbidden` | Invalid authentication, IP blacklisted, or domain not authorized |

### Example Request

```
GET /api?type=plugin&domain=example.com&key=your-api-key&slug=my-plugin&version=1.0.0
```

### Example Response (Update Available)

**Status:** `200 OK`

**Headers:**
```
Content-Type: application/zip
Content-Disposition: attachment; filename="my-plugin_1.1.0.zip"
```

**Body:** Binary ZIP file contents

### Example Response (No Update)

**Status:** `204 No Content`

No response body.

### Security

- All requests are logged with domain, date, and status
- Failed authentication attempts are tracked per IP address
- IPs are automatically blacklisted after 3 failed login attempts
- Blacklisted IPs are automatically removed after 7 days
- Non-blacklisted IPs with no activity are removed after 3 days

### Rate Limiting

The API uses IP-based blacklisting for rate limiting. After 3 failed authentication attempts, an IP will be blacklisted for 7 days.

---

## Roadmap

- [X] **`Task 1`**: <strike>Convert to MVC framework</strike>
- [X] **`Task 2`**: <strike>Implement more advanced authorization for site connections</strike>
- [X] **`Task 3`**: <strike>Implement Database instead of useing filesystem</strike>
- [ ] **`Task 4`**: Implement ability to remove ips from blacklist
- [ ] **`Task 5`**: Implement plug-in verification on upload
- [ ] **`Task 6`**: Implement docker version
      
---

## License

V-wordpress-plugin-updater is protected under the [LICENSE](https://choosealicense.com/licenses) License. For more details, refer to the [LICENSE](https://choosealicense.com/licenses/) file.

---
