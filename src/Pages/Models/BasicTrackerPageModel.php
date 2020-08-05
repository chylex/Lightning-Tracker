<?php
declare(strict_types = 1);

namespace Pages\Models;

use Database\Objects\TrackerInfo;
use Pages\Components\Navigation\NavigationComponent;
use Pages\Components\Text;
use Pages\Models\Tracker\MembersModel;
use Pages\Models\Tracker\SettingsModel;
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
    if ($perms->checkTracker($this->tracker, MembersModel::PERM_LIST)){
      $nav->addLeft(Text::withIcon('Members', 'users'), '/members');
    }
    
    if ($perms->checkTracker($this->tracker, SettingsModel::PERM)){
      $nav->addLeft(Text::withIcon('Settings', 'wrench'), '/settings');
    }
  }
  
  public function getTracker(): TrackerInfo{
    return $this->tracker;
  }
}

?>
