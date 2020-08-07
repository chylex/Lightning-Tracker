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
  private ?IssueDetail $issue = null;
  private int $issue_id;
  private bool $can_edit;
  
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
    
    if ($issue === null){
      $this->can_edit = false;
    }
    else{
      $author = $issue->getAuthor();
      $assignee = $issue->getAssignee();
      
      $this->issue = $issue;
      
      $this->can_edit = (
          ($author !== null && $logon_user_id === $author->getId()) ||
          ($assignee !== null && $logon_user_id === $assignee->getId()) ||
          $this->perms->checkTracker($tracker, IssuesModel::PERM_EDIT_ALL)
      );
      
      if ($this->can_edit){
        $this->menu_actions->addLink(Text::withIcon('Edit Issue', 'pencil'), '/issues/'.$this->issue_id.'/edit');
      }
      
      if ($this->perms->checkTracker($tracker, IssuesModel::PERM_DELETE_ALL)){
        $this->menu_actions->addLink(Text::withIcon('Delete Issue', 'trash'), '/issues/'.$this->issue_id.'/delete');
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
}

?>
