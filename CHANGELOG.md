# Changelog

All notable changes to this project will be documented in this file.
See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

## Unreleased
- Moved validation helpers to `App\Helpers\Validation` and encryption helpers to `App\Helpers\Encryption`.
- Added `App\Models\Blacklist` for IP blacklist management and removed `App\Core\Utility`.
- Introduced centralized `SessionManager` and `Csrf` utilities, refactored controllers and routing to use them, and replaced `AuthController` with `LoginController`.
- Switched router to instantiate controllers, dropped unused account/user/info routes, and added `/api` endpoint.
- Updated `LoginController` to render views through `$this` instead of creating a new instance.
