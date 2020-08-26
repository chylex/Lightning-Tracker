<?php
declare(strict_types = 1);

namespace Pages\Controllers\Root;

use Database\Objects\ProjectInfo;
use Generator;
use Pages\Controllers\AbstractProjectController;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\Controllers\Handlers\RequireSystemPermission;
use Pages\IAction;
use Pages\Models\Root\ProjectDeleteModel;
use Pages\Views\Root\ProjectDeletePage;
use Routing\Link;
use Routing\Request;
use Session\Permissions\SystemPermissions;
use Session\Session;
use function Pages\Actions\redirect;
use function Pages\Actions\view;

class ProjectDeleteController extends AbstractProjectController{
  protected function projectPrerequisites(ProjectInfo $project): Generator{
    yield new RequireLoginState(true);
    yield new RequireSystemPermission(SystemPermissions::LIST_PUBLIC_PROJECTS);
    yield new RequireSystemPermission(SystemPermissions::MANAGE_PROJECTS);
  }
  
  protected function projectFinally(Request $req, Session $sess, ProjectInfo $project): IAction{
    $model = new ProjectDeleteModel($req, $project);
    
    if ($req->getAction() === $model::ACTION_CONFIRM && $model->deleteProject($req->getData())){
      return redirect(Link::fromRoot());
    }
    
    return view(new ProjectDeletePage($model->load()));
  }
}

?>
