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
use Routing\Link;
use Routing\Request;
use Session\Session;
use function Pages\Actions\redirect;
use function Pages\Actions\view;

class LoginController extends AbstractHandlerController{
  public static function getReturnQuery(Request $req): string{
    $base_path_components = [BASE_PATH, $req->getBasePath()->raw()];
    $base_path = implode('/', array_filter(array_map(fn($v): string => ltrim($v, '/'), $base_path_components), fn($v): bool => !empty($v)));
    $base_path_len = mb_strlen($base_path);
    
    $request_uri = ltrim($_SERVER['REQUEST_URI'], '/');
    $current_path = '/'.parse_url($request_uri, PHP_URL_PATH);
    
    if (mb_str_ends_with($current_path, '/register')){
      $return = '';
    }
    elseif (mb_str_ends_with($current_path, '/login')){
      $return = $_GET['return'] ?? '';
    }
    else{
      $return = mb_substr($request_uri, 0, $base_path_len) === $base_path ? rawurlencode(ltrim(mb_substr($request_uri, $base_path_len), '/')) : '';
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
    
    if ($req->getAction() === $model::ACTION_LOGIN && $model->loginUser($req->getData(), $sess)){
      $return = $_GET['return'] ?? '';
      $return = strpos($return, '://') === false ? ltrim($return, '/') : '';
      return redirect(Link::fromBase($req, $return));
    }
    
    return view(new LoginPage($model->load()));
  }
}

?>
