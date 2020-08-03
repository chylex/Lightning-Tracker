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

function handle_error(int $code, string $title, string $message): void{
  http_response_code($code);
  $page_model = new BasicRootPageModel(new Request('', '', []));
  $error_model = new ErrorModel($page_model, $title, $message);
  view(new ErrorPage($error_model->load()))->execute();
}

try{
  $router->route($route);
}catch(RouterException $e){
  Log::critical($e);
  
  $code = $e->getCode();
  
  if ($code === RouterException::STATUS_NOT_FOUND){
    handle_error($code, 'Not Found', $e->getMessage());
  }
  else{
    handle_error($code, 'Fatal Error', 'An error occurred while handling your request.');
  }
}catch(Exception $e){
  Log::critical($e);
  handle_error(RouterException::STATUS_SERVER_ERROR, 'Fatal Error', 'An error occurred while handling your request.');
}
?>
