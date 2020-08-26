<?php
declare(strict_types = 1);

namespace Pages\Controllers\Project;

use Database\Objects\ProjectInfo;
use Pages\Controllers\AbstractProjectController;
use Pages\IAction;
use Pages\Models\BasicProjectPageModel;
use Pages\Views\Project\DashboardPage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\view;

class DashboardController extends AbstractProjectController{
  protected function projectFinally(Request $req, Session $sess, ProjectInfo $project): IAction{
    $model = new BasicProjectPageModel($req, $project);
    
    // TODO
    
    return view(new DashboardPage($model->load()));
  }
}

?>
