# V WP Dashboard REST API Schema

This document describes the REST API surface implemented in the `VWPDashboard\\Api` namespace.
All endpoints are registered under the namespace `vwpd/v1` and require an `X-API-Key` header that
matches the stored update key.

## Authentication

- **Header:** `X-API-Key`
- **Value:** A string that must match the value saved under the `update_key` option.
- **Failure Responses:**
  - `401 Unauthorized` with error codes `missing_api_key` or `invalid_api_key` when the header is
    absent or invalid.

---

## Plugins Endpoint `/plugins`

- **Base URL:** `/wp-json/vwpd/v1/plugins`
- **Methods:** `GET`, `POST`

### GET `/plugins`

Retrieves metadata for every installed plugin.

**Query Parameters:** _None_

**Success Response:** `200 OK`

```json
{
  "success": true,
  "plugins": [
    {
      "name": "string", // Human-readable plugin name
      "version": "string", // Installed version
      "slug": "string", // Directory name of the plugin
      "file": "string", // Plugin entry file path (e.g. "plugin-dir/plugin.php")
      "active": true // Whether the plugin is active
    }
  ],
  "count": 1 // Number of plugins returned
}
```

**Failure Responses:**

- `500 Internal Server Error` with code `plugin_list_error` when plugin metadata cannot be retrieved.

### POST `/plugins`

Installs a plugin from an uploaded ZIP package.

**Request Body:** `multipart/form-data`

- `package` (file, required): Plugin ZIP archive to install.

**Success Response:** `200 OK`

```json
{
  "success": true,
  "message": "Plugin installed successfully."
}
```

**Failure Responses:**

- `400 Bad Request` with code `missing_package` when no file is uploaded.
- `400 Bad Request` with code `upload_error` when the file upload fails validation or transmission.
- `500 Internal Server Error` with code `install_failed` if the installer returns an error or `false`.
- `500 Internal Server Error` with code `plugin_install_error` for unexpected exceptions.
- `500 Internal Server Error` with code `skin_errors` when installation feedback reports problems.

---

## Themes Endpoint `/themes`

- **Base URL:** `/wp-json/vwpd/v1/themes`
- **Methods:** `GET`, `POST`

### GET `/themes`

Retrieves metadata for every installed theme.

**Success Response:** `200 OK`

```json
{
  "success": true,
  "themes": [
    {
      "name": "string", // Theme display name
      "version": "string", // Installed version
      "slug": "string", // Theme directory slug
      "active": false // Whether the theme is the active stylesheet
    }
  ],
  "count": 1
}
```

**Failure Responses:**

- `500 Internal Server Error` with code `theme_list_error` if the theme list cannot be generated.

### POST `/themes`

Installs a theme from an uploaded ZIP package.

**Request Body:** `multipart/form-data`

- `package` (file, required): Theme ZIP archive to install.

**Success Response:** `200 OK`

```json
{
  "success": true,
  "message": "Theme installed successfully."
}
```

**Failure Responses:**

- `400 Bad Request` with code `missing_package` when no file is uploaded.
- `400 Bad Request` with code `upload_error` when the uploaded ZIP cannot be processed.
- `500 Internal Server Error` with code `install_failed` when the upgrader reports failure.
- `500 Internal Server Error` with code `theme_install_error` for unexpected exceptions.
- `500 Internal Server Error` with code `skin_errors` if validation or install steps encounter problems.

---

## Debug Log Endpoint `/debuglog`

- **Base URL:** `/wp-json/vwpd/v1/debuglog`
- **Methods:** `GET`

### GET `/debuglog`

Fetches the tail of the WordPress debug log.

**Query Parameters:**

- `lines` (integer, optional, default: `100`)
  - Number of lines to return from the end of the log file. Values < 1 reset to 100.

**Success Response:** `200 OK`

```json
{
  "success": true,
  "lines": 100,
  "log": [
    "string", // Each entry is a raw line from the debug log
    "string"
  ]
}
```

**Failure Responses:**

- `404 Not Found` when the debug log file does not exist, returning:
  ```json
  {
    "success": false,
    "message": "Debug log file not found.",
    "log": []
  }
  ```

---

## Common Error Format

Errors emitted by the API use the standard WordPress `WP_Error` structure. REST responses encapsulate
these errors as:

```json
{
  "code": "string", // Error identifier (see endpoint-specific tables)
  "message": "string",
  "data": {
    "status": 500 // HTTP status code matching the response
  }
}
```

All endpoints log success and failure states using the `VWPDashboard\\Helpers\\Logger` service.
