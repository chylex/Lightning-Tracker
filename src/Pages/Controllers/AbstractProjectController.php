<?php
declare(strict_types = 1);

namespace Pages\Controllers;

use Database\Objects\ProjectInfo;
use Generator;
use Pages\Controllers\Handlers\LoadProject;
use Pages\IAction;
use Routing\Request;
use Session\Session;

abstract class AbstractProjectController extends AbstractHandlerController{
  private ?ProjectInfo $project;
  
  protected final function prerequisites(): Generator{
    yield new LoadProject($this->project);
    yield from $this->projectPrerequisites($this->project);
  }
  
  protected final function finally(Request $req, Session $sess): IAction{
    return $this->projectFinally($req, $sess, $this->project);
  }
  
  /** @noinspection PhpUnusedParameterInspection */
  protected function projectPrerequisites(ProjectInfo $project): Generator{
    yield from [];
  }
  
  protected abstract function projectFinally(Request $req, Session $sess, ProjectInfo $project): IAction;
}

?>
