<?php
declare(strict_types = 1);

namespace Pages\Controllers;

use Database\Objects\TrackerInfo;
use Generator;
use Pages\Controllers\Handlers\LoadTracker;
use Pages\IAction;
use Routing\Request;
use Session\Session;

abstract class AbstractTrackerController extends AbstractHandlerController{
  private ?TrackerInfo $tracker;
  
  protected final function prerequisites(): Generator{
    yield new LoadTracker($this->tracker);
    yield from $this->trackerHandlers($this->tracker);
  }
  
  protected final function finally(Request $req, Session $sess): IAction{
    return $this->runTracker($req, $sess, $this->tracker);
  }
  
  /** @noinspection PhpUnusedParameterInspection */
  protected function trackerHandlers(TrackerInfo $tracker): Generator{
    yield from [];
  }
  
  protected abstract function runTracker(Request $req, Session $sess, TrackerInfo $tracker): IAction;
}

?>
