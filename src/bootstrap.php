<?php
declare(strict_types = 1);

use Logging\Log;
use Routing\Request;
use Routing\Router;
use Routing\RouterException;
use Routing\UrlString;
use function Pages\Actions\error;

define('TRACKER_PUBLIC_VERSION', '0.1');
define('TRACKER_MIGRATION_VERSION', 5);
define('TRACKER_RESOURCE_VERSION', ''); // autogenerated

define('CONFIG_FILE', __DIR__.'/config.php');
define('CONFIG_BACKUP_FILE', __DIR__.'/config.old.php');

setlocale(LC_ALL, 'C');
date_default_timezone_set('UTC');
header_remove('x-powered-by');

// Bootstrap

spl_autoload_extensions('.php');
spl_autoload_register(function($class){
  // default autoload implementation is garbage because
  // it converts paths to lowercase and breaks on linux
  /** @noinspection PhpIncludeInspection */
  require __DIR__.'/'.str_replace('\\', '/', $class).'.php';
});

require_once 'utils.php';

if (!file_exists('config.php')){
  require_once 'install.php';
  return;
}

/** @noinspection PhpIncludeInspection */
require_once 'config.php';

if (!defined('DEBUG')){
  define('DEBUG', false);
}

// Base URL

$base_url_split = mb_strpos(BASE_URL, '://');

if ($base_url_split === false){
  die('Base URL is invalid.');
}

$base_url_path = rtrim(parse_url(BASE_URL, PHP_URL_PATH) ?? '', '/');
$base_url_protocol = mb_substr(BASE_URL, 0, $base_url_split + 3);
$base_url_domain_path = mb_substr(BASE_URL, $base_url_split + 3);

define('BASE_PATH', $base_url_path);
define('BASE_PATH_ENC', (new UrlString($base_url_path))->encoded());
define('BASE_URL_ENC', $base_url_protocol.(new UrlString($base_url_domain_path))->encoded());

// Migration

if (TRACKER_MIGRATION_VERSION > INSTALLED_MIGRATION_VERSION){
  require_once 'update.php';
}

// Protection

if (!empty($_POST) && isset($_SERVER['HTTP_ORIGIN'])){
  $hostname = parse_url($_SERVER['HTTP_ORIGIN'] ?? 'null', PHP_URL_HOST);
  
  // Checking the Origin header and setting SameSite=Lax on the login
  // token cookie should be sufficient for preventing CSRF.
  
  if ($hostname === null || $hostname !== parse_url(BASE_URL, PHP_URL_HOST)){
    die('Could not validate the origin of your request.');
  }
}

// Route

require_once 'Pages/Actions/actions.php';

$route = isset($_GET['route']) ? $_GET['route'] : '';
unset($_GET['route']);

$router = new Router();

$router->add('&/', 'Root/ProjectsController');
$router->add('&/about', 'Root/AboutController');
$router->add('&/users', 'Root/UsersController');
$router->add('&/users/:id', 'Root/UserEditController');
$router->add('&/users/:id/delete', 'Root/UserDeleteController');
$router->add('&/settings', 'Root/SettingsController');

$router->add('project/:project/&', 'Project/DashboardController');
$router->add('project/:project/&/issues', 'Project/IssuesController');
$router->add('project/:project/&/issues/new', 'Project/IssueEditController');
$router->add('project/:project/&/issues/:id', 'Project/IssueDetailController');
$router->add('project/:project/&/issues/:id/edit', 'Project/IssueEditController');
$router->add('project/:project/&/issues/:id/delete', 'Project/IssueDeleteController');
$router->add('project/:project/&/milestones', 'Project/MilestonesController');
$router->add('project/:project/&/milestones/:id', 'Project/MilestoneEditController');
$router->add('project/:project/&/milestones/:id/delete', 'Project/MilestoneDeleteController');
$router->add('project/:project/&/members', 'Project/MembersController');
$router->add('project/:project/&/members/:id', 'Project/MemberEditController');
$router->add('project/:project/&/settings', 'Project/SettingsGeneralController');
$router->add('project/:project/&/settings/roles', 'Project/SettingsRolesController');
$router->add('project/:project/&/settings/roles/:id', 'Project/SettingsRoleEditController');
$router->add('project/:project/&/delete', 'Root/ProjectDeleteController');

foreach(['&/', 'project/:project/&/'] as $base){
  $router->add($base.'login', 'Mixed/LoginController');
  $router->add($base.'register', 'Mixed/RegisterController');
  $router->add($base.'account', 'Mixed/AccountController');
  $router->add($base.'account/appearance', 'Mixed/AccountAppearanceController');
  $router->add($base.'account/security', 'Mixed/AccountSecurityController');
}

$router->add('&/favicon.ico', 'Root/FaviconController');

function handle_error(int $code, string $title, string $message, ?Request $req = null): void{
  http_response_code($code);
  error($req ?? Request::empty(), $title, $message)->execute();
}

try{
  $router->route($route);
}catch(RouterException $e){
  Log::critical($e);
  
  $code = $e->getCode();
  $req = $e->getReq();
  
  if ($code === RouterException::STATUS_FORBIDDEN){
    handle_error($code, 'Permission Error', 'You do not have permission to perform this action.', $req);
  }
  elseif ($code === RouterException::STATUS_NOT_FOUND){
    handle_error($code, 'Not Found', $e->getMessage(), $req);
  }
  else{
    handle_error($code, 'Fatal Error', 'An error occurred while handling your request.', $req);
  }
}catch(Exception $e){
  Log::critical($e);
  handle_error(RouterException::STATUS_SERVER_ERROR, 'Fatal Error', 'An error occurred while handling your request.');
}
?>
