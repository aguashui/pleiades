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
- `cakephp/` — Git submodule pointing to CakePHP 2.10.24 framework core (`cakephp/lib/Cake/`).
- `app/Plugin/` — Bundled plugins (`Search`, `DebugKit`).
- `sass/` — SASS stylesheet source files (compiled using Compass).

---

## PHP Version Compatibility Guidelines

This project was originally written for PHP 5.3/5.4 and has been upgraded for compatibility with **PHP 8.1+ (PHP 8.1 through PHP 8.4)**.

When modifying or adding PHP code, strictly adhere to the following:

1. **Submodules**: Always run `git submodule update --init --recursive`. The `cakephp` submodule is pinned to CakePHP 2.10.24 (the final CakePHP 2.x release).
2. **Deprecated/Removed Functions**:
   - **DO NOT USE `split()`**: Replaced by `explode()` or `preg_split()`.
   - **DO NOT USE `each()`**: Replaced by `foreach()` or `key()`/`current()`.
   - **DO NOT USE `get_magic_quotes_gpc()`**: Obsolete in PHP 8.
   - **DO NOT USE `String` class**: Replaced by `CakeText` (e.g., `CakeText::tokenize()`, `CakeText::insert()`).
3. **Array/String Offset Syntax**:
   - **DO NOT USE curly braces for offsets** (e.g., `$str{0}`). Always use square brackets (`$str[0]`).
4. **Auth Component & Session Security**:
   - In `UsersController::beforeFilter()`, maintain `'login'` and `'logout'` in `$this->Auth->allow('view', 'login', 'logout')` to prevent redirect loops.
   - In `UsersController::logout()`, always delete the `isAdmin` session flag *before* redirecting.
   - In `AppController::isAdmin()`, verify that the user is authenticated via `$this->Auth->user('user_id')` in addition to checking the session flag.
5. **Redirects & File Uploads**:
   - Use `$this->referer('/', true)` to restrict redirects to local URLs and prevent open-redirect vulnerabilities.
   - Ensure upload validation checks `UPLOAD_ERR_OK`, `is_uploaded_file()`, and validates image types (e.g. `IMAGETYPE_PNG`) with `getimagesize()` before processing.

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
        'login' => 'pleiades_user',
        'password' => 'password',
        'database' => 'pleiades',
        'prefix' => '',
        'encoding' => 'utf8',
    );

    public $forum = array(
        'datasource' => 'Database/Mysql',
        'persistent' => false,
        'host' => 'localhost',
        'login' => 'phpbb_user',
        'password' => 'password',
        'database' => 'phpbb',
        'prefix' => 'phpbb_',
    );

    public $test = array(
        'datasource' => 'Database/Mysql',
        'persistent' => false,
        'host' => 'localhost',
        'login' => 'test_user',
        'password' => 'password',
        'database' => 'pleiades_test',
        'prefix' => '',
        'encoding' => 'utf8',
    );

    public $test_forum = array(
        'datasource' => 'Database/Mysql',
        'persistent' => false,
        'host' => 'localhost',
        'login' => 'test_user',
        'password' => 'password',
        'database' => 'phpbb_test',
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
- **Lint All PHP Files**:
  ```bash
  find app -name "*.php" -exec php -l {} +
  ```
- **Database Schema Initialization**:
  ```bash
  ./app/Console/cake schema create
  ```
