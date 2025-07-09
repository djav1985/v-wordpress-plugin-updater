# Copilot Instructions for V-WordPress-Plugin-Updater

## Overview
This project is a modular WordPress plugin and theme updater API. It integrates with WordPress installations to automate updates, enforce security, and provide a user-friendly admin interface. The architecture follows the MVC pattern, with clear separation of concerns.

---

## Key Components
### 1. **Routing**
- All requests are routed through `update-api/public/index.php`.
- Clean URLs are managed via `.htaccess`.
- API requests are handled by `ApiController` (`/api` route).

### 2. **Controllers**
- Located in `update-api/app/Controllers/`.
- Examples:
  - `ApiController`: Validates requests and serves plugin/theme updates.
  - `AuthController`: Handles login and authentication.
  - `PluginsController` & `ThemesController`: Manage plugin and theme updates.

### 3. **Models**
- Located in `update-api/app/Models/`.
- Handle data access for hosts, plugins, themes, and logs.

### 4. **Views**
- Located in `update-api/app/Views/`.
- Templates for admin UI (e.g., `home.php`, `logs.php`, `plupdate.php`).

### 5. **Storage**
- Logs and update packages are stored in `update-api/storage/`.

---

## Developer Workflows
### 1. **Setup**
- Clone the repository and configure `update-api/config.php`.
- Ensure the web server has write permissions for `update-api/storage/`.

### 2. **Testing API Requests**
- Example API URL:
  ```
  /api?type=plugin&domain=example.com&key=test&slug=plugin-slug&version=1.0.0
  ```
- Required query parameters:
  - `type`: `plugin` or `theme`.
  - `domain`: Domain name.
  - `key`: API key.
  - `slug`: Plugin/theme slug.
  - `version`: Current version.

### 3. **Debugging**
- Logs are stored in `update-api/storage/logs/`.
- Common errors:
  - `403 Forbidden`: IP blacklisted or invalid request method.
  - `400 Bad Request`: Missing or invalid query parameters.
  - `204 No Content`: No updates available.

---

## Project-Specific Conventions
1. **Security**
   - IP blacklisting is enforced via `Utility::isBlacklisted()`.
   - Domains and keys are validated against `update-api/HOSTS`.

2. **File Naming**
   - Update files follow the format: `{slug}_{version}.zip`.

3. **Error Handling**
   - Errors are logged using `ErrorMiddleware::logMessage()`.

---

## Integration Points
1. **WordPress**
   - `mu-plugin/` contains scripts for integrating with WordPress.
   - Define API constants in `wp-config.php`:
     ```php
     define('VONTMENT_KEY', 'your-api-key');
     define('VONTMENT_PLUGINS', 'https://example.com/update-api/public/api');
     define('VONTMENT_THEMES', 'https://example.com/update-api/public/api');
     ```

2. **External Dependencies**
   - Dropzone.js for file uploads in admin UI.
   - PSR-4 autoloader for `App` namespace.

---

## Examples
### Adding a New Route
1. Update `Router.php`:
   ```php
   case '/new-route':
       \App\Controllers\NewController::handleRequest();
       break;
   ```
2. Create `NewController.php` in `app/Controllers/`.

### Validating a New Parameter
1. Add the parameter to `$params` in `ApiController`.
2. Implement validation in `Utility.php`.

---

For further details, refer to the [README.md](../README.md).
