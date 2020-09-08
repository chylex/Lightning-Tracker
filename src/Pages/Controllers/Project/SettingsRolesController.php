<?php
declare(strict_types = 1);

namespace Pages\Controllers\Project;

use Database\Objects\ProjectInfo;
use Generator;
use Pages\Controllers\AbstractProjectController;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\Controllers\Handlers\RequireProjectPermission;
use Pages\IAction;
use Pages\Models\Project\SettingsRolesModel;
use Pages\Views\Project\SettingsRolesPage;
use Routing\Request;
use Session\Permissions\ProjectPermissions;
use Session\Session;
use function Pages\Actions\reload;
use function Pages\Actions\view;

class SettingsRolesController extends AbstractProjectController{
  protected function projectPrerequisites(ProjectInfo $project): Generator{
    yield new RequireLoginState(true);
    yield new RequireProjectPermission($project, ProjectPermissions::VIEW_SETTINGS);
  }
  
  protected function projectFinally(Request $req, Session $sess, ProjectInfo $project): IAction{
    $action = $req->getAction();
    $perms = $sess->getPermissions()->project($project);
    $model = new SettingsRolesModel($req, $project, $perms);
    
    if ($action !== null && $perms->require(ProjectPermissions::MANAGE_SETTINGS_ROLES)){
      $data = $req->getData();
      
      if (($action === $model::ACTION_CREATE && $model->createRole($data)) ||
          ($action === $model::ACTION_MOVE && $model->moveRole($data)) ||
          ($action === $model::ACTION_DELETE && $model->deleteRole($data))
      ){
        return reload();
      }
    }
    
    return view(new SettingsRolesPage($model->load()));
  }
}

?>
