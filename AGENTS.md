# AGENTS.md — Maintenance Guide for AI & Developers

This document provides context, conventions, and operational instructions for AI coding assistants and maintainers working on **Pleiades** (the Bitfighter Level Database).

---

## Project Overview

Pleiades is a web application built on **CakePHP 2.x** that serves as the level repository for the open-source multiplayer game Bitfighter.

### Key Architecture & Directory Structure

- `app/` — Application code:
  - `app/Controller/` — Controllers (`LevelsController`, `UsersController`, `CommentsController`, `NotificationsController`, `TagsController`, `PagesController`).
  - `app/Model/` — Data models (`Level`, `User`, `Comment`, `Notification`, `Rating`, `Tag`, `LevelsTag`).
  - `app/View/` — Views and templates (`.ctp` files).
  - `app/Config/` — Core, route, and database configuration (`core.php`, `routes.php`, `database.php`).
- `cakephp/` — Git submodule pointing to CakePHP 2.10.x framework core (`cakephp/lib/Cake/`).
- `app/plugins/remember_me/` — Git submodule for auto-login / remember-me functionality.
- `app/Plugin/` — Bundled plugins (`Search`, `DebugKit`).
- `sass/` — SASS stylesheet source files (compiled using Compass).

---

## PHP Version Compatibility Guidelines

This project was originally written for PHP 5.3/5.4 and has been upgraded for compatibility with **PHP 7.4+ and PHP 8.x**.

When modifying or adding PHP code, strictly adhere to the following:

1. **Submodules**: Always run `git submodule update --init --recursive`. The `cakephp` submodule must remain on CakePHP 2.10.24+ to ensure PHP 8 compatibility in core utilities.
2. **Deprecated/Removed Functions**:
   - **DO NOT USE `split()`**: Replaced by `explode()` or `preg_split()`.
   - **DO NOT USE `each()`**: Replaced by `foreach()` or `key()`/`current()`.
   - **DO NOT USE `get_magic_quotes_gpc()`**: Obsolete in PHP 8.
3. **Array/String Offset Syntax**:
   - **DO NOT USE curly braces for offsets** (e.g., `$str{0}`). Always use square brackets (`$str[0]`).
4. **Auth Component Configuration**:
   - In `UsersController::beforeFilter()`, always maintain `'login'` and `'logout'` in `$this->Auth->allow('view', 'login', 'logout')` to prevent infinite HTTP redirect loops when unauthenticated users access `/users/login`.
5. **Redirect Helpers**:
   - Use `$this->referer('/')` instead of `$this->referrer` in controllers.

---

## Database Configuration

The application requires two database connections defined in `app/Config/database.php`:
- `default`: Primary database for level data (`pleiades`).
- `forum`: Shared database with the phpBB3 instance (`phpbb`) for user authentication (`phpbb_users`, `phpbb_user_group`).

Template for `app/Config/database.php`:
```php
class DATABASE_CONFIG {
    public $default = array(
        'datasource' => 'Database/Mysql',
        'persistent' => false,
        'host' => 'localhost',
        'login' => 'root',
        'password' => 'root',
        'database' => 'pleiades',
        'prefix' => '',
        'encoding' => 'utf8',
    );

    public $forum = array(
        'datasource' => 'Database/Mysql',
        'persistent' => false,
        'host' => 'localhost',
        'login' => 'root',
        'password' => 'root',
        'database' => 'phpbb',
        'prefix' => 'phpbb_',
    );
}
```

---

## Local Development & Testing

- **Quick Local Server**:
  ```bash
  php -S localhost:8000 -t app/webroot
  ```
- **Run CLI Test Suite**:
  ```bash
  ./app/Console/cake test app AllTests
  ```
- **Lint All PHP Files**:
  ```bash
  find app -name "*.php" -exec php -l {} +
  ```
