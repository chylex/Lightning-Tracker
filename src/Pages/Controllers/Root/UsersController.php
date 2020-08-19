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
    $action = $req->getAction();
    $perms = $sess->getPermissions();
    $model = new UsersModel($req, $perms);
    
    if ($action !== null){
      $data = $req->getData();
      
      if (($action === $model::ACTION_CREATE && $perms->requireSystem($model::PERM_ADD) && $model->createUser($data)) ||
          ($action === $model::ACTION_DELETE && $perms->requireSystem($model::PERM_EDIT) && $model->deleteUser($data))
      ){
        return reload();
      }
    }
    
    return view(new UsersPage($model->load()));
  }
}

?>
