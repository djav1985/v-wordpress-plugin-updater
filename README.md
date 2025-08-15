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

The v-wordpress-plugin-updater project is designed to streamline the management and updating of WordPress plugins and themes through a robust API and automated processes. It offers a comprehensive solution for secure plugin and theme updates, including user authentication, IP blacklisting, and detailed logging. The project provides an admin interface for managing updates, handling uploads, and monitoring logs, ensuring seamless operation across different environments. With support for both single and multisite installations, this project enhances WordPress site maintenance efficiency by automating update checks and installations, significantly reducing manual intervention.

---

## Features

|      | Component            | Details                                                                                     |
| :--- | :------------------- | :------------------------------------------------------------------------------------------ |
| ⚙️  | **Architecture**     | <ul><li>PHP-based plugin architecture for WordPress</li><li>Follows standard plugin directory structure</li><li>Includes updater logic, hooks, and admin interfaces</li></ul> |
| 🔩 | **Code Quality**     | <ul><li>Uses PHP best practices with namespacing</li><li>Code is modular with separate classes/functions</li><li>Includes inline comments and docblocks</li></ul> |
| 📄 | **Documentation**    | <ul><li>README provides setup and usage instructions</li><li>Includes code comments and inline docs</li><li>No extensive external docs or API references</li></ul> |
| 🔌 | **Integrations**      | <ul><li>Integrates with WordPress hooks and filters</li><li>Uses REST API endpoints for updates</li><li>Reads configuration from JSON and ini files</li></ul> |
| 🧩 | **Modularity**        | <ul><li>Separate classes for updater, plugin info, and settings</li><li>Configurable via JSON files</li><li>Supports extension points for custom behaviors</li></ul> |
| 🧪 | **Testing**           | <ul><li>Limited unit tests present, primarily for core classes</li><li>Uses PHPUnit for testing PHP components</li><li>Test coverage appears minimal, mainly functional tests</li></ul> |
| ⚡️  | **Performance**       | <ul><li>Optimized file checks with caching mechanisms</li><li>Minimized HTTP requests during update checks</li><li>Uses transient caching in WordPress</li></ul> |
| 🛡️ | **Security**          | <ul><li>Sanitizes and validates external inputs</li><li>Uses nonces for admin actions</li><li>Reads configuration files with restricted permissions</li></ul> |
| 📦 | **Dependencies**      | <ul><li>PHP standard library</li><li>WordPress core functions</li><li>Configuration files: robots.txt, php.ini, etc.</li><li>SQLite database for persistence (e.g., updater.sqlite with blacklist table)</li></ul> |

---

## Project Structure

```sh
└── v-wordpress-plugin-updater/
    ├── .github
    │   └── copilot-instructions.md
    ├── LICENSE
    ├── README.md
    ├── mu-plugin
    │   ├── v-sys-plugin-updater-mu.php
    │   ├── v-sys-plugin-updater.php
    │   └── v-sys-theme-updater.php
    ├── update-api
    │   ├── HOSTS
    │   ├── app
    │   │   ├── Controllers
    │   │   │   ├── AccountsController.php
    │   │   │   ├── ApiController.php
    │   │   │   ├── HomeController.php
    │   │   │   ├── InfoController.php
    │   │   │   ├── LoginController.php
    │   │   │   ├── LogsController.php
    │   │   │   ├── PluginsController.php
    │   │   │   ├── ThemesController.php
    │   │   │   └── UsersController.php
    │   │   ├── Core
    │   │   │   ├── Controller.php
    │   │   │   ├── Csrf.php
    │   │   │   ├── ErrorManager.php
    │   │   │   ├── Router.php
    │   │   │   └── SessionManager.php
    │   │   ├── Helpers
    │   │   │   ├── Encryption.php
    │   │   │   ├── MessageHelper.php
    │   │   │   └── Validation.php
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
    │   │       ├── thupdate.php
    │   │       ├── accounts.php
    │   │       ├── info.php
    │   │       └── users.php
    │   ├── autoload.php
    │   ├── config.php
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
    │   │   └── robots.txt
    │   └── storage
    │       ├── updater.sqlite
    │       └── logs
    │           ├── php_app.log
    │           ├── plugin.log
    │           └── theme.log
    └── v-wordpress-plugin-updater.png
```

### Project Index

<details open>
	<summary><b><code>V-WORDPRESS-PLUGIN-UPDATER/</code></b></summary>
	<!-- __root__ Submodule -->
	<details>
		<summary><b>__root__</b></summary>
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
					<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/HOSTS'>HOSTS</a></b></td>
					<td style='padding: 8px;'>- Defines the host configurations for the update API, establishing the environment settings necessary for deploying and managing the API across different infrastructure targets<br>- It ensures consistent host references, facilitating seamless integration and communication within the overall system architecture<br>- This setup supports reliable deployment workflows and environment-specific customization for the update API component.</td>
				</tr>
				<tr style='border-bottom: 1px solid #eee;'>
					<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/config.php'>config.php</a></b></td>
					<td style='padding: 8px;'>- Defines core configuration constants for the WordPress Update API, establishing authentication parameters, directory paths, and session management settings<br>- These configurations facilitate secure and organized access to plugin, theme, and log storage, supporting the API’s role in managing and delivering updates within the overall project architecture.</td>
				</tr>
				<tr style='border-bottom: 1px solid #eee;'>
					<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/autoload.php'>autoload.php</a></b></td>
					<td style='padding: 8px;'>- Establishes a PSR-4 autoloading mechanism for the App namespace, enabling seamless and efficient loading of class files within the update-api project<br>- This autoloader supports the modular architecture by dynamically resolving class locations, ensuring organized code management and streamlined execution across the applications components.</td>
				</tr>
			</table>
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
                                                                        <td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/app/Views/accounts.php'>accounts.php</a></b></td>
                                                                        <td style='padding: 8px;'>- Placeholder view for managing accounts.</td>
                                                                </tr>
                                                                <tr style='border-bottom: 1px solid #eee;'>
                                                                        <td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/app/Views/info.php'>info.php</a></b></td>
                                                                        <td style='padding: 8px;'>- Placeholder view for displaying info.</td>
                                                                </tr>
                                                                <tr style='border-bottom: 1px solid #eee;'>
                                                                        <td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/update-api/app/Views/users.php'>users.php</a></b></td>
                                                                        <td style='padding: 8px;'>- Placeholder view for managing users.</td>
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
	<!-- mu-plugin Submodule -->
	<details>
		<summary><b>mu-plugin</b></summary>
		<blockquote>
			<div class='directory-path' style='padding: 8px 0; color: #666;'>
				<code><b>⦿ mu-plugin</b></code>
			<table style='width: 100%; border-collapse: collapse;'>
			<thead>
				<tr style='background-color: #f8f9fa;'>
					<th style='width: 30%; text-align: left; padding: 8px;'>File Name</th>
					<th style='text-align: left; padding: 8px;'>Summary</th>
				</tr>
			</thead>
				<tr style='border-bottom: 1px solid #eee;'>
					<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/mu-plugin/v-sys-plugin-updater.php'>v-sys-plugin-updater.php</a></b></td>
					<td style='padding: 8px;'>- Implements automated daily checks and updates for WordPress plugins by retrieving, downloading, and installing newer plugin versions from a remote API<br>- Integrates seamlessly into the WordPress lifecycle to ensure plugins remain current, enhancing site security and functionality without manual intervention<br>- Serves as a core component of the update management architecture within the broader WordPress plugin ecosystem.</td>
				</tr>
				<tr style='border-bottom: 1px solid #eee;'>
					<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/mu-plugin/v-sys-theme-updater.php'>v-sys-theme-updater.php</a></b></td>
					<td style='padding: 8px;'>- Implements automated daily updates for WordPress themes by checking for new versions, downloading update packages, and applying updates seamlessly<br>- Integrates with the WordPress update API to ensure themes remain current, enhancing site security and functionality while minimizing manual intervention within the overall WordPress architecture.</td>
				</tr>
				<tr style='border-bottom: 1px solid #eee;'>
					<td style='padding: 8px;'><b><a href='https://github.com/djav1985/v-wordpress-plugin-updater/blob/master/mu-plugin/v-sys-plugin-updater-mu.php'>v-sys-plugin-updater-mu.php</a></b></td>
					<td style='padding: 8px;'>- Implements automated daily plugin update checks and installations for a WordPress multisite environment<br>- It ensures plugins are kept current by retrieving updates from a remote API, downloading, and installing them seamlessly, thereby maintaining site security and functionality without manual intervention<br>- The process is optimized for main site management, enhancing overall WordPress maintenance efficiency.</td>
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

1. Clone or download this repository inside your web server document root.
2. Create the following directories so the Update API can store packages and logs:

   ```sh
   mkdir -p /storage/plugins
   mkdir -p /storage/themes
   mkdir -p /storage/logs
   ```
3. Edit `/config.php` and set the login credentials and directory constants. Adjust `VALID_USERNAME`, `VALID_PASSWORD`, and paths under `BASE_DIR` if the defaults do not match your setup.
4. Set an `ENCRYPTION_KEY` environment variable used to secure host keys:

   ```sh
   export ENCRYPTION_KEY="your-32-byte-secret"
   ```
5. Define the API constants used by the mu-plugins in your WordPress `wp-config.php`:

   ```php
   define('VONTMENT_KEY', 'your-api-key');
   define('VONTMENT_PLUGINS', 'https://example.com/api');
   define('VONTMENT_THEMES', 'https://example.com/api');
   ```
6. Ensure the web server user owns the `/storage` directory so uploads and logs can be written.

7. From the `update-api/` directory run `php install.php` to create the SQLite database and required tables, including the blacklist. Ensure `storage/updater.sqlite` is writable by the web server.

8. Configure a system cron to run `php cron.php` regularly so the database stays in sync with the plugin and theme directories.

NOTE: Make sure to set /public/ as doc root.

### Usage

1. Copy the files from the repository's `mu-plugin/` folder into your WordPress installation's `wp-content/mu-plugins/` directory. Create the directory if it doesn't exist. WordPress automatically loads any PHP files placed here.
2. Run install.php to update from v3 to v4 imports and handles conversion to db.
3. Log in to the Update API by visiting the `/login` route (handled by `index.php`) using the credentials configured in `config.php` to manage hosts, plugins and themes.

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
