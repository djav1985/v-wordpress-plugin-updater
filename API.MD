# Update API Reference

This document describes how a client communicates with the Update API server to check for and retrieve plugin or theme updates.

## Endpoint

Send requests to the API endpoint configured on the server:

```text
https://updates.example.com/api
```

The endpoint accepts **GET** requests only. When configuring the WordPress client plugin, use the complete URL ending in `/api` for the Plugin Update URL and/or Theme Update URL.

## Before making requests

1. In the Update API admin panel, add the WordPress site's domain as an authorized host and obtain its API key.
2. Configure that domain and key in the WordPress client plugin.
3. Upload the plugin or theme ZIP to the API server. Name the archive `{slug}_{version}.zip`, for example `my-plugin_1.2.3.zip`. The server synchronizes package files with its update metadata; run the configured cron sync after adding or changing packages.

The `domain` parameter is the host name of the WordPress site's `site_url()` (for example, `example.com`), not a URL with a scheme or path. The host must match the authorized host entry.

## Request

All five parameters are required and are sent as URL-encoded query parameters:

| Parameter | Description | Example | Validation |
|---|---|---|---|
| `type` | Package type | `plugin` or `theme` | Must be exactly `plugin` or `theme`. |
| `domain` | Authorized WordPress host name | `example.com` | Must be a valid domain name. |
| `key` | API key assigned to that host | `a1b2c3-example_key` | Letters, numbers, hyphens, and underscores; no whitespace. |
| `slug` | Plugin or theme identifier | `my-plugin` | Letters, numbers, dots, underscores, and hyphens. |
| `version` | Currently installed version | `1.2.3` | Numeric components separated by dots, such as `2.0` or `1.2.3`. |

Example request:

```http
GET /api?type=plugin&domain=example.com&key=YOUR_API_KEY&slug=my-plugin&version=1.2.3 HTTP/1.1
Host: updates.example.com
```

Equivalent `curl.exe` request (for example, from PowerShell):

```powershell
curl.exe --get "https://updates.example.com/api" `
  --data-urlencode "type=plugin" `
  --data-urlencode "domain=example.com" `
  --data-urlencode "key=YOUR_API_KEY" `
  --data-urlencode "slug=my-plugin" `
  --data-urlencode "version=1.2.3" `
  --dump-header response-headers.txt `
  --output response.body
```

Check the HTTP status before treating `response.body` as a package: a successful update response contains a ZIP archive, while a no-update response has no body.

## Responses

| Status | Meaning |
|---|---|
| `200 OK` | A newer package is available. The response body is the ZIP file, with `Content-Type: application/octet-stream`, a `Content-Disposition` filename such as `my-plugin_1.3.0.zip`, and `Content-Length`. |
| `204 No Content` | The installed version is current or newer. There is no response body. |
| `400 Bad Request` | A required parameter is missing or a parameter is invalid. |
| `403 Forbidden` | The request IP is missing/invalid or blacklisted, the domain is unknown, or the domain's API key is incorrect. |
| `404 Not Found` | Authentication succeeded, but the requested plugin or theme slug is not registered. |
| `405 Method Not Allowed` | The request method is not `GET`. |
| `500 Internal Server Error` | An update is registered, but its ZIP file is missing or unreadable. |

Error responses do not include a JSON error object; use the HTTP status code to determine the outcome.

## Update decision and package naming

After successful authentication, the API compares the requested `version` with the latest version registered for the given `type` and `slug`. It returns `200` only when the registered version is newer than the supplied version. Otherwise it returns `204`.

The package file must be stored on the server using this exact pattern:

```text
{slug}_{version}.zip
```

The slug and version in the filename must match the package metadata registered by the server.

## Security and operational notes

- Serve the API over HTTPS. The API key is sent in the query string, so TLS is required to protect it in transit.
- Query strings can be captured by web server, proxy, or monitoring logs. Protect those logs and do not share request URLs containing real keys.
- Failed authentication attempts (unknown host or incorrect key) are counted by client IP. After three failed attempts, that IP is blacklisted for seven days.
- Invalid request parameters (`400`) and authenticated requests for unknown slugs (`404`) do not consume the failed-authentication limit.
- The WordPress updater handles `200` by saving the ZIP response for installation, `204` as no update, and `403` as an authorization failure. Other HTTP statuses or network errors are treated as update errors.
