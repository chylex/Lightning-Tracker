<?php
declare(strict_types = 1);

namespace Pages\Models;

use Database\Objects\TrackerInfo;
use Routing\Request;

class BasicMixedPageModel extends AbstractWrapperModel{
  public function __construct(Request $req, ?TrackerInfo $tracker){
    parent::__construct(($tracker === null) ? new BasicRootPageModel($req) : new BasicTrackerPageModel($req, $tracker));
  }
}

?>
