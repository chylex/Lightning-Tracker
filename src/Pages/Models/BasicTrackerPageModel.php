<?php
declare(strict_types = 1);

namespace Pages\Models;

use Database\Objects\TrackerInfo;
use Pages\Components\Navigation\NavigationComponent;
use Pages\Components\Text;
use Routing\Request;
use Session\Permissions;

class BasicTrackerPageModel extends AbstractPageModel{
  private TrackerInfo $tracker;
  
  public function __construct(Request $req, TrackerInfo $tracker){
    parent::__construct($req);
    $this->tracker = $tracker;
  }
  
  protected function createNavigation(): NavigationComponent{
    return new NavigationComponent($this->tracker->getNameSafe(), BASE_URL_ENC, $this->getReq());
  }
  
  protected function setupNavigation(NavigationComponent $nav, Permissions $perms): void{
  }
  
  public function getTracker(): TrackerInfo{
    return $this->tracker;
  }
}

?>
