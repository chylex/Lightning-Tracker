<?php
declare(strict_types = 1);

namespace Pages\Controllers\Project;

use Database\Objects\ProjectInfo;
use Generator;
use Pages\Controllers\AbstractProjectController;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\Controllers\Handlers\RequireProjectPermission;
use Pages\IAction;
use Pages\Models\Project\SettingsDescriptionModel;
use Pages\Views\Project\SettingsDescriptionPage;
use Routing\Request;
use Session\Permissions\ProjectPermissions;
use Session\Session;
use function Pages\Actions\view;

class SettingsDescriptionController extends AbstractProjectController{
  protected function projectPrerequisites(ProjectInfo $project): Generator{
    yield new RequireLoginState(true);
    yield new RequireProjectPermission($project, ProjectPermissions::VIEW_SETTINGS);
  }
  
  protected function projectFinally(Request $req, Session $sess, ProjectInfo $project): IAction{
    $perms = $sess->getPermissions()->project($project);
    $model = new SettingsDescriptionModel($req, $project, $perms);
    
    if ($req->getAction() === $model::ACTION_UPDATE && $perms->require(ProjectPermissions::MANAGE_SETTINGS_DESCRIPTION) && $model->updateDescription($req->getData())){
      return $model->getEditDescriptionForm()->reload($req);
    }
    
    return view(new SettingsDescriptionPage($model->load()));
  }
}

?>
