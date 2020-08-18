<?php
declare(strict_types = 1);

namespace Pages\Controllers\Mixed;

use Database\Objects\TrackerInfo;
use Generator;
use Pages\Controllers\AbstractHandlerController;
use Pages\Controllers\Handlers\LoadTracker;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\IAction;
use Pages\Models\Mixed\LoginModel;
use Pages\Views\Mixed\LoginPage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\redirect;
use function Pages\Actions\view;

class LoginController extends AbstractHandlerController{
  private static function strEndsWith(string $haystack, string $needle): bool{
    return substr($haystack, -strlen($needle)) === $needle;
  }
  
  public static function getReturnQuery(Request $req): string{
    $base_path_components = [BASE_PATH, $req->getBasePath()->raw()];
    $base_path = implode('/', array_filter(array_map(fn($v): string => ltrim($v, '/'), $base_path_components), fn($v): bool => !empty($v)));
    $base_path_len = strlen($base_path);
    
    $request_uri = ltrim($_SERVER['REQUEST_URI'], '/');
    $current_path = '/'.parse_url($request_uri, PHP_URL_PATH);
    
    if (self::strEndsWith($current_path, '/register')){
      $return = '';
    }
    elseif (self::strEndsWith($current_path, '/login')){
      $return = $_GET['return'] ?? '';
    }
    else{
      $return = substr($request_uri, 0, $base_path_len) === $base_path ? rawurlencode(ltrim(substr($request_uri, $base_path_len), '/')) : '';
    }
    
    return empty($return) ? '' : '?return='.$return;
  }
  
  private ?TrackerInfo $tracker;
  
  protected function prerequisites(): Generator{
    yield new RequireLoginState(false);
    yield (new LoadTracker($this->tracker))->allowMissing();
  }
  
  protected function finally(Request $req, Session $sess): IAction{
    $model = new LoginModel($req, $this->tracker);
    $data = $req->getData();
    
    if (!empty($data) && $model->loginUser($data, $sess)){
      $return = $_GET['return'] ?? '';
      $return = strpos($return, '://') === false ? ltrim($return, '/') : '';
      return redirect([BASE_URL_ENC, $req->getBasePath()->encoded(), $return]);
    }
    
    return view(new LoginPage($model->load()));
  }
}

?>
