<?php
declare(strict_types = 1);

namespace Pages\Controllers\Root;

use Generator;
use Pages\Controllers\AbstractHandlerController;
use Pages\Controllers\Handlers\HandleFilteringRequest;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\Controllers\Handlers\RequireSystemPermission;
use Pages\IAction;
use Pages\Models\Root\UsersModel;
use Pages\Views\Root\UsersPage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\reload;
use function Pages\Actions\view;

class UsersController extends AbstractHandlerController{
  protected function prerequisites(): Generator{
    yield new RequireLoginState(true);
    yield new RequireSystemPermission(UsersModel::PERM_LIST);
    yield new HandleFilteringRequest();
  }
  
  protected function finally(Request $req, Session $sess): IAction{
    $perms = $sess->getPermissions();
    $model = new UsersModel($req, $perms);
    
    if ($req->getAction() === $model::ACTION_CREATE && $perms->requireSystem($model::PERM_ADD) && $model->createUser($req->getData())){
      return reload();
    }
    
    return view(new UsersPage($model->load()));
  }
}

?>
