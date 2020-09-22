<?php
declare(strict_types = 1);

namespace Pages\Controllers\Root;

use Generator;
use Pages\Controllers\AbstractHandlerController;
use Pages\Controllers\Handlers\HandleFilteringRequest;
use Pages\IAction;
use Pages\Models\Root\ProjectModel;
use Pages\Views\Root\ProjectsPage;
use Routing\Link;
use Routing\Request;
use Session\Permissions\SystemPermissions;
use Session\Session;
use function Pages\Actions\message;
use function Pages\Actions\redirect;
use function Pages\Actions\view;

class ProjectsController extends AbstractHandlerController{
  protected function prerequisites(): Generator{
    yield new HandleFilteringRequest();
  }
  
  protected function finally(Request $req, Session $sess): IAction{
    $perms = $sess->getPermissions()->system();
    $model = new ProjectModel($req, $perms, $sess->getLogonUserId());
    
    if ($req->getAction() === $model::ACTION_CREATE){
      $logon_user = $sess->getLogonUser();
      
      if ($logon_user === null || !$perms->check(SystemPermissions::LIST_VISIBLE_PROJECTS) || !$perms->check(SystemPermissions::CREATE_PROJECT)){
        return message($req, 'Permission Error', 'You do not have permission to create a project.');
      }
      
      $url = $model->createProject($req->getData(), $logon_user);
      
      if ($url !== null){
        return redirect(Link::fromRoot('project', rawurlencode($url)));
      }
    }
    
    return view(new ProjectsPage($model->load()));
  }
}

?>
