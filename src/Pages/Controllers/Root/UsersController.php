<?php
declare(strict_types = 1);

namespace Pages\Controllers\Root;

use Generator;
use Pages\Components\Forms\FormComponent;
use Pages\Controllers\AbstractHandlerController;
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
  }
  
  protected function finally(Request $req, Session $sess): IAction{
    $model = new UsersModel($req, $sess->getPermissions());
    $data = $req->getData();
    
    if (!empty($data)){
      $action = $data[FormComponent::ACTION_KEY] ?? '';
      
      if (($action === $model::ACTION_CREATE && $model->createUser($data)) ||
          ($action === $model::ACTION_DELETE && $model->deleteUser($data))
      ){
        return reload();
      }
    }
    
    return view(new UsersPage($model->load()));
  }
}

?>
