<?php
declare(strict_types = 1);

namespace Pages\Controllers\Project;

use Database\Objects\ProjectInfo;
use Generator;
use Pages\Controllers\AbstractProjectController;
use Pages\Controllers\Handlers\LoadNumericId;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\Controllers\Handlers\RequireProjectPermission;
use Pages\IAction;
use Pages\Models\Project\SettingsRoleEditModel;
use Pages\Views\Project\SettingsRoleEditPage;
use Routing\Link;
use Routing\Request;
use Session\Permissions\ProjectPermissions;
use Session\Session;
use function Pages\Actions\redirect;
use function Pages\Actions\view;

class SettingsRoleEditController extends AbstractProjectController{
  private ?int $role_id;
  
  protected function projectPrerequisites(ProjectInfo $project): Generator{
    yield new RequireLoginState(true);
    yield new RequireProjectPermission($project, ProjectPermissions::MANAGE_SETTINGS);
    yield new LoadNumericId($this->role_id, 'role', $project);
  }
  
  protected function projectFinally(Request $req, Session $sess, ProjectInfo $project): IAction{
    $model = new SettingsRoleEditModel($req, $project, $this->role_id);
    
    if ($req->getAction() === $model::ACTION_CONFIRM && $model->editRole($req->getData())){
      return redirect(Link::fromBase($req, 'settings', 'roles'));
    }
    
    return view(new SettingsRoleEditPage($model->load()));
  }
}

?>
