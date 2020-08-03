<?php
declare(strict_types = 1);

namespace Pages\Controllers\Mixed;

use Generator;
use Pages\Controllers\AbstractHandlerController;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\IAction;
use Pages\Models\Mixed\LoginModel;
use Pages\Views\Mixed\LoginPage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\redirect;
use function Pages\Actions\view;

class LoginController extends AbstractHandlerController{
  protected function prerequisites(): Generator{
    yield new RequireLoginState(false);
  }
  
  protected function finally(Request $req, Session $sess): IAction{
    $model = new LoginModel($req);
    $data = $req->getData();
    
    if (!empty($data) && $model->loginUser($data, $sess)){
      return redirect([BASE_URL_ENC,
                       $req->getBasePath()->encoded(),
                       isset($_GET['return']) ? ltrim($_GET['return'], '/') : '']);
    }
    
    return view(new LoginPage($model->load()));
  }
}

?>
