<?php
declare(strict_types = 1);

namespace Pages\Models;

use Database\DB;
use Database\Objects\MilestoneProgress;
use Database\Objects\ProjectInfo;
use Database\Tables\ProjectUserSettingsTable;
use Exception;
use Logging\Log;
use Pages\Components\CompositeComponent;
use Pages\Components\Html;
use Pages\Components\Navigation\NavigationComponent;
use Pages\Components\ProgressBarComponent;
use Pages\Components\Text;
use Pages\Controllers\Mixed\LoginController;
use Pages\IViewable;
use Routing\Request;
use Session\PermissionManager;
use Session\Permissions\ProjectPermissions;
use Session\Session;

class BasicProjectPageModel extends AbstractPageModel{
  private ProjectInfo $project;
  
  private bool $loaded_active_milestone = false;
  private ?MilestoneProgress $active_milestone = null;
  
  public function __construct(Request $req, ProjectInfo $project){
    parent::__construct($req);
    $this->project = $project;
  }
  
  protected function createNavigation(): NavigationComponent{
    return new NavigationComponent($this->project->getNameSafe(), BASE_URL_ENC, $this->getReq()->getBasePath(), $this->getReq()->getRelativePath());
  }
  
  protected function setupNavigation(NavigationComponent $nav, PermissionManager $perms): void{
    $nav->addLeft(Text::withIcon('Dashboard', 'chart'), '');
    $nav->addLeft(Text::withIcon('Issues', 'notification'), '/issues');
    $nav->addLeft(Text::withIcon('Milestones', 'calendar'), '/milestones');
    
    $perms = $perms->project($this->project);
    
    if ($perms->check(ProjectPermissions::LIST_MEMBERS)){
      $nav->addLeft(Text::withIcon('Members', 'users'), '/members');
    }
    
    if ($perms->check(ProjectPermissions::MANAGE_SETTINGS)){
      $nav->addLeft(Text::withIcon('Settings', 'wrench'), '/settings');
    }
  }
  
  protected function getLoginReturnQuery(): string{
    return LoginController::getReturnQuery($this->getReq());
  }
  
  public function getProject(): ProjectInfo{
    return $this->project;
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
        $settings = new ProjectUserSettingsTable(DB::get(), $this->getProject());
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
    
    return new CompositeComponent(new Html('<h3>Active Milestone</h3><article id="active-milestone"><h4>'.$milestone->getTitleSafe().'</h4>'),
                                  new ProgressBarComponent($milestone->getPercentageDone()),
                                  new Html('</article>'));
  }
}

?>
