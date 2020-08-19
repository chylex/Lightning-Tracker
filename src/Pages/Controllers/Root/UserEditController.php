<?php
declare(strict_types = 1);

namespace Pages\Controllers\Root;

use Generator;
use Pages\Controllers\AbstractHandlerController;
use Pages\Controllers\Handlers\LoadNumericId;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\Controllers\Handlers\RequireSystemPermission;
use Pages\IAction;
use Pages\Models\Root\UserEditModel;
use Pages\Models\Root\UsersModel;
use Pages\Views\Root\UserEditPage;
use Routing\Link;
use Routing\Request;
use Session\Session;
use function Pages\Actions\redirect;
use function Pages\Actions\view;

class UserEditController extends AbstractHandlerController{
  private ?int $user_id;
  
  protected function prerequisites(): Generator{
    yield new RequireLoginState(true);
    yield new RequireSystemPermission(UsersModel::PERM_EDIT);
    yield new LoadNumericId($this->user_id, 'user');
  }
  
  protected function finally(Request $req, Session $sess): IAction{
    $model = new UserEditModel($req, $this->user_id);
    
    if ($req->getAction() === $model::ACTION_CONFIRM && $model->editUser($req->getData())){
      return redirect(Link::fromBase($req, 'users'));
    }
    
    return view(new UserEditPage($model->load()));
  }
}

?>
