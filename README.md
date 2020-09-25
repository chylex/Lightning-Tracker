**Lightning Tracker** is a completely free, easy to use issue tracker built for PHP 7.4+ and MySQL 5.6+.

Its primary purpose is to help developers — individuals or teams — keep track of their work, but it also supports public-facing projects where any registered user can file an issue.

The application is still work-in-progress, but the first official release should be available soon. You can also build the application from source at your own risk. Please read the installation instructions carefully, and see the Roadmap below to find out which features are currently missing and planned for a future release.

---

![Issue Details](https://github.com/chylex/Lightning-Tracker/blob/master/.github/img/issue.png)

---

## Feature Highlights

* Milestones help you track progress across multiple issues. Marking a milestone as **active** shows its progress in a widget on the side while you browse issues.
* Every issue can be given a **scale**, which determines how much it contributes to the overall completion % of its assigned **milestone**.
* A role and permission system lets you decide who can see which projects, who can file an issue, and who can manage various parts of the project (issues, milestones, members, settings).
* The website can function entirely **without JavaScript**. Scripting is only an enhancement, not a requirement.

### Notes on Roles & Permissions

The administrator account has full control over the tracker and every project in it. Similarly, a project owner has full control over their project.

The **Manage Projects** permission allows a tracker user to act as a project owner for any project that is visible to them. When combined with the **View All Projects** permission, that user will have full control over every project.

Every role has a set order, such that a tracker user can only edit and delete users of lower roles, and not of equal or higher roles. They also cannot assign their role or a higher role to any new or existing user. The same applies to project roles in regards to inviting members, assigning roles, and removing members from a project.

## Roadmap

There is no timeline for when each feature will be implemented, as this is an open-source project I work on in my free time.

* **Users & Management**:
  * Add a way to recover an account via email.
  * Add notifications for various events, and a way to follow issues.
  * Moderation tools needed to prevent and mitigate spam and abuse in public-facing projects.
* **Project Dashboard**:
  * Overview of the entire project, your active milestone, fancy graphs, and other cool things.
* **Issue Page**
  * Add comments and attachments.
* **Issue Filtering**:
  * Add editable quick filters.
* **Editor Formatting**:
  * Add buttons for all supported formatting options.
  * Although the full CommonMark/GFM spec will not be supported, it should support anything that's reasonable.

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
3. Create a new InnoDB database, do not reuse an existing one
4. Visit the website and enter your configuration and credentials

#### From Source

**Be warned that until the first official release, the update process might fail between certain commits, and require manual intervention.**

1. Clone the repository
2. Run the `gulp` task inside the `/build/` folder
3. Copy everything inside the newly created `/out/` folder to your web server
4. Create a new InnoDB database, do not reuse an existing one
5. Visit the website and enter your configuration and credentials

#### Updating

1. Create a backup of your database and the installation folder
2. Copy all files to your web server
3. Visit the website to initiate the update process

If the update process fails, look into your server logs to find the error. After remedying the error, visit the website again and the update process will resume.

If the error is an issue with Lightning Tracker, please [file an issue](https://github.com/chylex/Lightning-Tracker/issues) and revert your database and installation folder using the backup you made.

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

While developing tests, it is inconvenient to have to re-run the entire test suite which takes several minutes to complete. Instead, you can edit `tests/acceptance.suite.yml` and include a test into the `core` group, then only run the specified tests (after the pre-defined setup test) using `codecept run -g core` or the provided `Test (Core)` run configuration.

#### Code Coverage

In order to run the test suite with coverage enabled, first ensure you can run the tests normally by following the **Automated Testing** section.

Then, ensure code coverage is enabled on your test server. Personally I recommend [PCOV](https://github.com/krakjoe/pcov) over [Xdebug](https://xdebug.org/) because it runs much faster and also has slightly better accuracy, but you can use either. Put the following into your `php.ini` and uncomment one of the lines to specify which extension to use:

```ini
[xdebug]
;zend_extension=xdebug
xdebug.remote_autostart=on
xdebug.remote_enable=on
xdebug.remote_host=127.0.0.1
xdebug.remote_port=9000
xdebug.remote_handler=dbgp
xdebug.remote_mode=req
xdebug.coverage_enable=on

[pcov]
;extension=pcov
pcov.enabled=1
pcov.directory=./
```

1. Run the `gulp prepareCoverage` task in the `/build/` folder
2. Run the tests using `codecept run --coverage --coverage-xml --coverage-html` or the provided `Test (Cover)` run configuration
3. View the results
   * Browser: open `/tests/_output/acceptance.remote.coverage/index.html`
   * PhpStorm: edit `/tests/_output/acceptance.remote.coverage.xml` to replace all paths pointing to `/server/www/` with `/src/`, then import using the **Show Code Coverage Data** dialog
4. The original `/server/www/` folder was backed up under `/server/www-backup/`, when running code coverage you will have to restore the backup manually by deleting `www` and renaming `www-backup`
