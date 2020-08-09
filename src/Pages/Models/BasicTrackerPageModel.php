<?php
declare(strict_types = 1);

namespace Pages\Models;

use Database\DB;
use Database\Objects\MilestoneProgress;
use Database\Objects\TrackerInfo;
use Database\Tables\TrackerUserSettingsTable;
use Exception;
use Logging\Log;
use Pages\Components\CompositeComponent;
use Pages\Components\Navigation\NavigationComponent;
use Pages\Components\ProgressBarComponent;
use Pages\Components\Text;
use Pages\IViewable;
use Pages\Models\Tracker\MembersModel;
use Pages\Models\Tracker\SettingsModel;
use Routing\Request;
use Session\Permissions;
use Session\Session;

class BasicTrackerPageModel extends AbstractPageModel{
  private TrackerInfo $tracker;
  
  private bool $loaded_active_milestone = false;
  private ?MilestoneProgress $active_milestone = null;
  
  public function __construct(Request $req, TrackerInfo $tracker){
    parent::__construct($req);
    $this->tracker = $tracker;
  }
  
  protected function createNavigation(): NavigationComponent{
    return new NavigationComponent($this->tracker->getNameSafe(), BASE_URL_ENC, $this->getReq()->getBasePath(), $this->getReq()->getRelativePath());
  }
  
  protected function setupNavigation(NavigationComponent $nav, Permissions $perms): void{
    $nav->addLeft(Text::withIcon('Dashboard', 'stats-dots'), '');
    $nav->addLeft(Text::withIcon('Issues', 'info'), '/issues');
    $nav->addLeft(Text::withIcon('Milestones', 'calendar'), '/milestones');
    
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
  
  public function getActiveMilestone(): ?MilestoneProgress{
    if ($this->loaded_active_milestone){
      return $this->active_milestone;
    }
    
    $this->loaded_active_milestone = true;
    $logon_user = Session::get()->getLogonUser();
    
    if ($logon_user === null){
      $milestone = null;
    }
    else{
      try{
        $settings = new TrackerUserSettingsTable(DB::get(), $this->getTracker());
        $milestone = $settings->getActiveMilestoneProgress($logon_user);
      }catch(Exception $e){
        Log::critical($e);
        $milestone = null;
      }
    }
    
    $this->active_milestone = $milestone;
    return $this->active_milestone;
  }
  
  public function getActiveMilestoneComponent(): ?IViewable{
    $milestone = $this->getActiveMilestone();
    
    if ($milestone === null){
      return null;
    }
    
    return new CompositeComponent(Text::plain('<h3>Active Milestone</h3><article>'),
                                  new ProgressBarComponent($milestone->getPercentageDone()),
                                  Text::plain('</article>'));
  }
}

?>
