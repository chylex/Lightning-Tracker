<?php
declare(strict_types = 1);

use Logging\Log;
use Pages\Models\BasicRootPageModel;
use Pages\Models\ErrorModel;
use Pages\Views\ErrorPage;
use Routing\Request;
use Routing\Router;
use Routing\RouterException;
use Routing\UrlString;
use function Pages\Actions\view;

if (version_compare(PHP_VERSION, '7.4', '<')){
  die('Lightning Tracker requires PHP 7.4 or newer.');
}

define('TRACKER_VERSION', '0.1');

setlocale(LC_ALL, 'C');
date_default_timezone_set('UTC');
header_remove('x-powered-by');

// Bootstrap

spl_autoload_extensions('.php');
spl_autoload_register();

require_once 'config.php';

$base_url_split = mb_strpos(BASE_URL, '://');

if ($base_url_split === false){
  die('Base URL is invalid.');
}

$base_url_protocol = mb_substr(BASE_URL, 0, $base_url_split + 3);
$base_url_path = new UrlString(mb_substr(BASE_URL, $base_url_split + 3));

define('BASE_URL_ENC', $base_url_protocol.$base_url_path->encoded());

// Utilities

require_once 'Database/utils.php';
require_once 'Pages/Actions/actions.php';

// Route

$route = isset($_GET['route']) ? $_GET['route'] : '';
unset($_GET['route']);

$router = new Router();

$router->add('&/', 'Root/TrackersController');
$router->add('&/about', 'Root/AboutController');
$router->add('&/users', 'Root/UsersController');
$router->add('&/settings', 'Root/SettingsController');

$router->add('tracker/:tracker/&', 'Tracker/DashboardController');
$router->add('tracker/:tracker/&/issues', 'Tracker/IssuesController');
$router->add('tracker/:tracker/&/issues/new', 'Tracker/IssueEditController');
$router->add('tracker/:tracker/&/issues/:id', 'Tracker/IssueDetailController');
$router->add('tracker/:tracker/&/issues/:id/edit', 'Tracker/IssueEditController');
$router->add('tracker/:tracker/&/issues/:id/delete', 'Tracker/IssueDeleteController');
$router->add('tracker/:tracker/&/milestones', 'Tracker/MilestonesController');
$router->add('tracker/:tracker/&/members', 'Tracker/MembersController');
$router->add('tracker/:tracker/&/settings', 'Tracker/SettingsController');

foreach(['&/', 'tracker/:tracker/&/'] as $base){
  $router->add($base.'login', 'Mixed/LoginController');
  $router->add($base.'register', 'Mixed/RegisterController');
  $router->add($base.'account', 'Mixed/AccountController');
  $router->add($base.'account/security', 'Mixed/AccountSecurityController');
}

// TODO CSRF

function handle_error(int $code, string $title, string $message, ?Request $req = null): void{
  http_response_code($code);
  $page_model = new BasicRootPageModel($req ?? new Request('', '', []));
  $error_model = new ErrorModel($page_model, $title, $message);
  view(new ErrorPage($error_model->load()))->execute();
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
