<?php
declare(strict_types = 1);

namespace Pages\Controllers\Tracker;

use Database\Objects\TrackerInfo;
use Pages\Controllers\AbstractTrackerController;
use Pages\IAction;
use Pages\Models\Tracker\IssuesModel;
use Pages\Views\Tracker\IssuesPage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\view;

class IssuesController extends AbstractTrackerController{
  protected function runTracker(Request $req, Session $sess, TrackerInfo $tracker): IAction{
    return view(new IssuesPage((new IssuesModel($req, $tracker, $sess->getPermissions()))->load()));
  }
}

?>
