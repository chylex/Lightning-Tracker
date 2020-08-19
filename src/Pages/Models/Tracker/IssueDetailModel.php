<?php
declare(strict_types = 1);

namespace Pages\Models\Tracker;

use Database\DB;
use Database\Objects\IssueDetail;
use Database\Objects\TrackerInfo;
use Database\Tables\IssueTable;
use Pages\Components\Markdown\MarkdownParseResult;
use Pages\Components\Sidemenu\SidemenuComponent;
use Pages\Components\Text;
use Pages\IModel;
use Pages\Models\BasicTrackerPageModel;
use Routing\Request;
use Session\Permissions;
use Session\Session;

class IssueDetailModel extends BasicTrackerPageModel{
  public const ACTION_UPDATE_TASKS = 'Update';
  public const CHECKBOX_NAME = 'Tasks';
  
  private ?IssueDetail $issue = null;
  private int $issue_id;
  private bool $can_edit;
  
  private Permissions $perms;
  private MarkdownParseResult $description;
  private SidemenuComponent $menu_actions;
  
  public function __construct(Request $req, TrackerInfo $tracker, Permissions $perms, int $issue_id){
    parent::__construct($req, $tracker);
    
    $this->perms = $perms;
    $this->issue_id = $issue_id;
    
    $this->menu_actions = new SidemenuComponent($req);
    $this->menu_actions->setTitle(Text::plain('Actions'));
  }
  
  public function load(): IModel{
    parent::load();
    
    $tracker = $this->getTracker();
    
    $issues = new IssueTable(DB::get(), $tracker);
    $issue = $issues->getIssueDetail($this->issue_id);
    
    if ($issue === null){
      $this->can_edit = false;
    }
    else{
      $logon_user = Session::get()->getLogonUser();
      
      $this->issue = $issue;
      $this->can_edit = $logon_user !== null && ($issue->isAuthorOrAssignee($logon_user) || $this->perms->checkTracker($tracker, IssuesModel::PERM_EDIT_ALL));
      
      if ($this->can_edit){
        $this->menu_actions->addLink(Text::withIcon('Edit Issue', 'pencil'), '/issues/'.$this->issue_id.'/edit');
      }
      
      if ($this->perms->checkTracker($tracker, IssuesModel::PERM_DELETE_ALL)){
        $this->menu_actions->addLink(Text::withIcon('Delete Issue', 'trash'), '/issues/'.$this->issue_id.'/delete');
      }
      
      $desc = $issue->getDescription();
      
      if ($this->can_edit){
        $desc->setCheckboxNameForEditing(self::CHECKBOX_NAME);
      }
      
      $this->description = $desc->parse();
    }
    
    return $this;
  }
  
  public function canEditCheckboxes(): bool{
    return $this->can_edit;
  }
  
  public function getIssue(): ?IssueDetail{
    return $this->issue;
  }
  
  public function getIssueId(): int{
    return $this->issue_id;
  }
  
  public function getDescription(): MarkdownParseResult{
    return $this->description;
  }
  
  public function getMenuActions(): ?SidemenuComponent{
    return $this->menu_actions->getIfNotEmpty();
  }
  
  public function updateCheckboxes(array $data): void{
    $issues = new IssueTable(DB::get(), $this->getTracker());
    $description = $issues->getIssueDescription($this->issue_id);
    
    $checked_indices = array_map(fn($i): int => intval($i), $data[self::CHECKBOX_NAME] ?? []);
    $index = 0;
    
    $description = preg_replace_callback(IssueEditModel::TASK_REGEX, function(array $matches) use ($checked_indices, &$index): string{
      return in_array(++$index, $checked_indices, true) ? '['.IssueEditModel::TASK_CHECKED_CHARS[0].']' : '[ ]';
    }, $description);
    
    if ($index > 0){
      $issues->updateIssueTasks($this->issue_id, $description, (int)floor(100.0 * count($checked_indices) / $index));
    }
  }
}

?>
