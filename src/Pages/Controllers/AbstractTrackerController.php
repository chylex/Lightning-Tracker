<?php
declare(strict_types = 1);

namespace Pages\Controllers;

use Database\Objects\TrackerInfo;
use Generator;
use Pages\Controllers\Handlers\RequireTracker;
use Pages\IAction;
use Routing\Request;
use Session\Session;

abstract class AbstractTrackerController extends AbstractHandlerController{
  private ?TrackerInfo $tracker;
  
  protected final function prerequisites(): Generator{
    yield new RequireTracker($this->tracker);
    yield from $this->trackerHandlers($this->tracker);
  }
  
  protected final function finally(Request $req, Session $sess): IAction{
    return $this->runTracker($req, $sess, $this->tracker);
  }
  
  protected function trackerHandlers(TrackerInfo $tracker): Generator{
    yield from [];
  }
  
  protected abstract function runTracker(Request $req, Session $sess, TrackerInfo $tracker): IAction;
}

?>
