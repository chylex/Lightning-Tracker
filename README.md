Lightning Tracker is a work-in-progress issue tracker built for PHP 7.4+ and MySQL 5.6+.

**I do not recommend using the tracker at this moment.** The current version lacks a fully-fledged permission system and many upcoming major features.

## Installation Guide

### Server Requirements

* **PHP 7.4+**
  * **pdo**
  * **pdo_mysql**
  * **mbstring**
  * **intl**
* **MySQL 5.6+**
  * **InnoDB**
* **Apache**
  * **mod_rewrite**
  * mod_deflate (optional)

You should be able to install Lightning Tracker on other server software, such as nginx or IIS, but you will have to replicate the behavior of `.htaccess` on your own.

### Installation

1. Download the latest [release](https://github.com/chylex/Lightning-Tracker/releases).
2. Copy everything inside the `src` and `res` folders to your web server.
3. Create a new InnoDB database.
4. Visit your web server and enter your configuration and credentials.

#### Notes

* Base URL must be identical to the URL leading to the main page, including the protocol (`http://` or `https://`). The field is pre-filled with the URL you used to access it, and the default value should work.
* If the installation page appears without any styles, you may need to edit your Apache settings (or `.htaccess` if you do not have access to the server configuration) to include a path to the tracker installation folder. For example, if you place the tracker into a folder called `tracker`, you may need to change:
   * This line: `RewriteCond %{REQUEST_URI} !^/~(generated|resources)/`
   * To this: `RewriteCond %{REQUEST_URI} !^/tracker/~(generated|resources)/`
