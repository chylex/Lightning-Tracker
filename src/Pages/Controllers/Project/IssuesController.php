<?php
declare(strict_types = 1);

namespace Pages\Controllers\Project;

use Database\Objects\ProjectInfo;
use Generator;
use Pages\Controllers\AbstractProjectController;
use Pages\Controllers\Handlers\HandleFilteringRequest;
use Pages\IAction;
use Pages\Models\Project\IssuesModel;
use Pages\Views\Project\IssuesPage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\view;

class IssuesController extends AbstractProjectController{
  protected function projectPrerequisites(ProjectInfo $project): Generator{
    yield new HandleFilteringRequest();
  }
  
  protected function projectFinally(Request $req, Session $sess, ProjectInfo $project): IAction{
    return view(new IssuesPage((new IssuesModel($req, $project, $sess->getPermissions()->project($project)))->load()));
  }
}

?>
