# Pleiades - The Bitfighter Level Database

*Find your place among the stars*

## Installing

### Dependencies

 * PHP (PHP 7.4+ or 8.x supported), MySQL / MariaDB, Apache properly configured
 * Apache `mod_rewrite` enabled
 * `AllowOverride All` enabled so that `.htaccess` rules take effect
 * PHP GD module
 * Functional phpBB3 instance (for user authentication)
 * Git (for retrieving dependencies as submodules)
 * A database for Pleiades (e.g. `pleiades`) and phpBB (`phpbb`)

### Development Dependencies

 * PHPUnit
 * XDebug
 * A test database (e.g. `pleiades_test`)
 * The `compass` gem from rubygems.org (for SASS compilation)

### Instructions

 1. Clone the repo: `git clone https://github.com/bitfighter/pleiades.git`
 2. `cd pleiades`
 3. Initialize and update submodules: `git submodule update --init --recursive`
    *(Note: CakePHP core is maintained as a submodule at `cakephp/` pinned to CakePHP 2.10.x for PHP 8+ compatibility).*
 4. `cd app/Config` and `cp database.php.default database.php`.
 5. Edit `database.php` with your MySQL connection details for both the `default` and `forum` datasources.
 6. If this is a production server, edit `app/Config/core.php` and set `Configure::write('debug', 0);`.
 7. Load the database schema into MySQL: `mysql -uroot -p pleiades < app/Config/Schema/schema_4.php` (or run `./app/Console/cake schema create`).
 8. Point your web server document root to `app/webroot` (or run `php -S localhost:8000 -t app/webroot` for local testing).

### Development instructions

 1. To run tests, visit `localhost/pleiades/test.php` or execute `./app/Console/cake test app AllTests`.
 2. To work on the stylesheets, run `compass watch sass` from the root directory and edit `sass/src/pleiades.scss`.
 3. Hackity hack, and send your pull requests to bitfighter on github :)
 4. Deploy script: `./deploy.sh` updates the production server.

## Maintenance Notes

For maintenance guidelines and instructions for AI coding assistants working on this codebase, see [AGENTS.md](AGENTS.md).

## Acknowledgements

My sincere thanks to Watusimoto and raptor on #bitfighter at irc.freenode.net
for writing a game that inspired me to create such a preposterous contraption,
and for their patience during this process.
