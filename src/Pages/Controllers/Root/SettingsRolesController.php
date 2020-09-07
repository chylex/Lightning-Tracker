<?php
declare(strict_types = 1);

namespace Pages\Controllers\Root;

use Generator;
use Pages\Controllers\AbstractHandlerController;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\Controllers\Handlers\RequireSystemPermission;
use Pages\IAction;
use Pages\Models\Root\SettingsRolesModel;
use Pages\Views\Root\SettingsRolesPage;
use Routing\Request;
use Session\Permissions\SystemPermissions;
use Session\Session;
use function Pages\Actions\reload;
use function Pages\Actions\view;

class SettingsRolesController extends AbstractHandlerController{
  protected function prerequisites(): Generator{
    yield new RequireLoginState(true);
    yield new RequireSystemPermission(SystemPermissions::MANAGE_SETTINGS);
  }
  
  protected function finally(Request $req, Session $sess): IAction{
    $action = $req->getAction();
    $model = new SettingsRolesModel($req);
    
    if (($action === $model::ACTION_CREATE && $model->createRole($req->getData())) ||
        ($action === $model::ACTION_MOVE && $model->moveRole($req->getData())) ||
        ($action === $model::ACTION_DELETE && $model->deleteRole($req->getData()))
    ){
      return reload();
    }
    
    return view(new SettingsRolesPage($model->load()));
  }
}

?>
