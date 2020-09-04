Lightning Tracker is a work-in-progress issue tracker built for PHP 7.4+ and MySQL 5.6+.

**I do not recommend using the tracker at this moment.** The current version lacks a fully-fledged permission system and many upcoming major features.

## Installation Guide

### Server Requirements

* **PHP 7.4+**
  * **pdo**
  * **pdo_mysql**
  * **mbstring**
  * **json**
  * **intl**
* **MySQL 5.6+**
  * **InnoDB**
* **Apache**
  * **mod_rewrite**
  * mod_deflate (optional)

You should be able to install Lightning Tracker on other server software, such as nginx or IIS, but you will have to replicate the behavior of `.htaccess` on your own.

### Installation

#### Official Releases

1. Download an [official release](https://github.com/chylex/Lightning-Tracker/releases)
2. Copy everything inside the downloaded archive to your web server
3. Create a new InnoDB database
4. Visit your web server and enter your configuration and credentials

#### From Source

1. Clone the repository
2. Run the `gulp` task inside the `/build/` folder
3. Copy everything inside the newly created `/out/` folder to your web server
4. Create a new InnoDB database
5. Visit your web server and enter your configuration and credentials

#### Notes

* Base URL must be identical to the URL leading to the main page, including the protocol (`http://` or `https://`). The field is pre-filled with the URL you used to access it, and the default value should work.
* If the installation page appears without any styles, you may need to edit your Apache settings (or `.htaccess` if you do not have access to the server configuration) to include a path to the tracker installation folder. For example, if you place the tracker into a folder called `tracker`, you may need to change:
   * This line: `RewriteCond %{REQUEST_URI} !^/~resources/`
   * To this: `RewriteCond %{REQUEST_URI} !^/tracker/~resources/`

### Source Code

#### Test Server

The project is setup so that `http://localhost` maps to `/server/www/` in the repository root folder. If you use PhpStorm, it should automatically upload modified files to this folder.

To enable debug mode, which bypasses manual installation, caching, and required minification of resources, copy all files from `/dev/` to `/server/www/`. You will also need a MySQL server with the following credentials:

* **Host:** `localhost`
* **User:** `lt`
* **Password:** `test`
* **Database:** `tracker`
 
#### Automated Testing

The project uses the [Codeception](https://codeception.com/) test framework. Before you proceed, make sure you have `php`, `composer`, and `gulp` installed in your PATH.

1. Run `composer install` in the repository root folder to install dependencies
2. Setup a web server as outlined in the **Test Server** section above
3. Create a MySQL database named `tracker_test` and grant the `lt` user all privileges:
    * `GRANT ALL PRIVILEGES ON tracker_test.* to 'lt'@'localhost';`
4. Run the `gulp prepareTests` task in the `/build/` folder
5. Run the tests by running the following command in the repository root folder:
   * Windows: `codecept run`
   * (Otherwise): `php vendor/codeception/codeception/codecept run`

If you use PhpStorm, steps 4 and 5 are included under the provided `Test` run configuration. You may also use the `Test (Debug)` configuration which runs in debug mode.
