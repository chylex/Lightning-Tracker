<?php
declare(strict_types = 1);

namespace Pages\Controllers\Root;

use Generator;
use Pages\Controllers\AbstractHandlerController;
use Pages\Controllers\Handlers\HandleFilteringRequest;
use Pages\IAction;
use Pages\Models\Root\ProjectModel;
use Pages\Views\Root\ProjectsPage;
use Routing\Request;
use Session\Permissions\SystemPermissions;
use Session\Session;
use function Pages\Actions\reload;
use function Pages\Actions\view;

class ProjectsController extends AbstractHandlerController{
  protected function prerequisites(): Generator{
    yield new HandleFilteringRequest();
  }
  
  protected function finally(Request $req, Session $sess): IAction{
    $perms = $sess->getPermissions()->system();
    $model = new ProjectModel($req, $perms);
    
    if ($req->getAction() === $model::ACTION_CREATE &&
        $perms->require(SystemPermissions::LIST_VISIBLE_PROJECTS) &&
        $perms->require(SystemPermissions::CREATE_PROJECT) &&
        $model->createProject($req->getData(), $sess->getLogonUser())
    ){
      return reload();
    }
    
    return view(new ProjectsPage($model->load()));
  }
}

?>
