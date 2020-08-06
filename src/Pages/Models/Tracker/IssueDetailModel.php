<?php
declare(strict_types = 1);

namespace Pages\Models\Tracker;

use Database\DB;
use Database\Objects\IssueDetail;
use Database\Objects\TrackerInfo;
use Database\Tables\IssueTable;
use Pages\Components\Sidemenu\SidemenuComponent;
use Pages\Components\Text;
use Pages\IModel;
use Pages\Models\BasicTrackerPageModel;
use Routing\Request;
use Session\Permissions;
use Session\Session;

class IssueDetailModel extends BasicTrackerPageModel{
  public const ACTION_DELETE = 'Delete';
  
  private ?IssueDetail $issue = null;
  private int $issue_id;
  
  private Permissions $perms;
  private SidemenuComponent $menu_actions;
  
  public function __construct(Request $req, TrackerInfo $tracker, Permissions $perms, int $issue_id){
    parent::__construct($req, $tracker);
    
    $this->perms = $perms;
    $this->issue_id = $issue_id;
    
    $this->menu_actions = new SidemenuComponent(BASE_URL_ENC, $req);
    $this->menu_actions->setTitle(Text::plain('Actions'));
  }
  
  public function load(): IModel{
    parent::load();
    
    $tracker = $this->getTracker();
    
    $logon_user = Session::get()->getLogonUser();
    $logon_user_id = $logon_user === null ? -1 : $logon_user->getId();
    
    $issues = new IssueTable(DB::get(), $tracker);
    $issue = $issues->getIssueDetail($this->issue_id);
    
    if ($issue !== null){
      $this->issue = $issue;
      
      if ($logon_user_id === $issue->getAuthor()->getId() || $this->perms->checkTracker($tracker, IssuesModel::PERM_EDIT_ALL)){
        $this->menu_actions->addLink(Text::withIcon('Edit Issue', 'pencil'), '/issues/'.$this->issue_id.'/edit');
      }
      
      if ($this->perms->checkTracker($tracker, IssuesModel::PERM_DELETE_ALL)){
        $this->menu_actions->addActionButton(Text::withIcon('Delete Issue', 'trash'), self::ACTION_DELETE);
      }
    }
    
    return $this;
  }
  
  public function getIssue(): ?IssueDetail{
    return $this->issue;
  }
  
  public function getIssueId(): int{
    return $this->issue_id;
  }
  
  public function getMenuActions(): SidemenuComponent{
    return $this->menu_actions;
  }
  
  public function deleteIssue(): void{ // TODO make it a dedicated page with additional checks (critical since this one doesn't ask first)
    $tracker = $this->getTracker();
    $this->perms->requireTracker($tracker, IssuesModel::PERM_DELETE_ALL); // TODO allow deleting own issues?
    
    $issues = new IssueTable(DB::get(), $tracker);
    $issues->deleteById($this->issue_id);
  }
}

?>
