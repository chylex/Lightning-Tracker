<?php
declare(strict_types = 1);

namespace Pages\Controllers\Project;

use Database\Objects\ProjectInfo;
use Generator;
use Pages\Controllers\AbstractProjectController;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\Controllers\Handlers\RequireProjectPermission;
use Pages\IAction;
use Pages\Models\Project\SettingsGeneralModel;
use Pages\Views\Project\SettingsGeneralPage;
use Routing\Request;
use Session\Permissions\ProjectPermissions;
use Session\Session;
use function Pages\Actions\view;

class SettingsGeneralController extends AbstractProjectController{
  protected function projectPrerequisites(ProjectInfo $project): Generator{
    yield new RequireLoginState(true);
    yield new RequireProjectPermission($project, ProjectPermissions::MANAGE_SETTINGS);
  }
  
  protected function projectFinally(Request $req, Session $sess, ProjectInfo $project): IAction{
    $model = new SettingsGeneralModel($req, $project);
    
    if ($req->getAction() === $model::ACTION_UPDATE && $model->updateSettings($req->getData())){
      return $model->getSettingsForm()->reload($req);
    }
    
    return view(new SettingsGeneralPage($model->load()));
  }
}

?>
