<?php
declare(strict_types = 1);

namespace Pages\Models\Project;

use Data\IssueStatus;
use Database\DB;
use Database\Objects\IssueDetail;
use Database\Objects\ProjectInfo;
use Database\Tables\IssueTable;
use Pages\Components\Markup\LightMarkParseResult;
use Pages\Components\Sidemenu\SidemenuComponent;
use Pages\Components\Text;
use Pages\Models\BasicProjectPageModel;
use Routing\Request;
use Session\Permissions\ProjectPermissions;
use Session\Session;

class IssueDetailModel extends BasicProjectPageModel{
  public const ACTION_UPDATE_TASKS = 'Update';
  public const CHECKBOX_NAME = 'Tasks';
  
  public const ACTION_MARK_READY_TO_TEST = 'MarkReadyToTest';
  public const ACTION_MARK_FINISHED = 'MarkFinished';
  public const ACTION_MARK_REJECTED = 'MarkRejected';
  
  private ProjectPermissions $perms;
  private int $issue_id;
  private ?IssueDetail $issue;
  private int $edit_level;
  
  public function __construct(Request $req, ProjectInfo $project, ProjectPermissions $perms, int $issue_id){
    parent::__construct($req, $project);
    $this->perms = $perms;
    $this->issue_id = $issue_id;
    
    $issues = new IssueTable(DB::get(), $project);
    $this->issue = $issues->getIssueDetail($issue_id);
    
    if ($this->issue === null){
      $this->edit_level = IssueDetail::EDIT_FORBIDDEN;
    }
    else{
      $this->edit_level = $this->issue->getEditLevel(Session::get()->getLogonUser(), $perms);
    }
  }
  
  public function getIssueId(): int{
    return $this->issue_id;
  }
  
  public function getIssue(): ?IssueDetail{
    return $this->issue;
  }
  
  public function canEditStatus(): bool{
    return $this->edit_level >= IssueDetail::EDIT_ALL_FIELDS;
  }
  
  public function parseDescription(): LightMarkParseResult{
    $desc = $this->issue->getDescription();
    
    if ($this->canEditStatus()){
      $desc->setCheckboxNameForEditing(self::CHECKBOX_NAME);
    }
    
    return $desc->parse();
  }
  
  public function createMenuActions(): ?SidemenuComponent{
    if ($this->issue === null){
      return null;
    }
    
    $menu = new SidemenuComponent($this->getReq());
    
    if ($this->edit_level !== IssueDetail::EDIT_FORBIDDEN){
      $menu->addLink(Text::withIcon('Edit Issue', 'pencil'), '/issues/'.$this->issue_id.'/edit');
    }
    
    if ($this->perms->check(ProjectPermissions::DELETE_ALL_ISSUES)){
      $menu->addLink(Text::withIcon('Delete Issue', 'trash'), '/issues/'.$this->issue_id.'/delete');
    }
    
    return $menu->getIfNotEmpty();
  }
  
  public function createMenuShortcuts(): ?SidemenuComponent{
    if ($this->issue === null || !$this->canEditStatus()){
      return null;
    }
    
    $menu = new SidemenuComponent($this->getReq());
    $menu->addActionButton(Text::withIssueTag('Mark as Ready to Test', IssueStatus::get(IssueStatus::READY_TO_TEST)), self::ACTION_MARK_READY_TO_TEST);
    $menu->addActionButton(Text::withIssueTag('Mark as Finished', IssueStatus::get(IssueStatus::FINISHED)), self::ACTION_MARK_FINISHED);
    $menu->addActionButton(Text::withIssueTag('Mark as Rejected', IssueStatus::get(IssueStatus::REJECTED)), self::ACTION_MARK_REJECTED);
    return $menu;
  }
  
  public function tryUseShortcut(string $action): bool{
    if (!$this->canEditStatus()){
      return false;
    }
    
    switch($action){
      case self::ACTION_MARK_READY_TO_TEST:
        $status = IssueStatus::READY_TO_TEST;
        break;
      
      case self::ACTION_MARK_FINISHED:
        $status = IssueStatus::FINISHED;
        break;
      
      case self::ACTION_MARK_REJECTED:
        $status = IssueStatus::REJECTED;
        break;
      
      default:
        return false;
    }
    
    $issues = new IssueTable(DB::get(), $this->getProject());
    $issues->updateIssueStatus($this->issue_id, IssueStatus::get($status), 100);
    return true;
  }
  
  public function updateCheckboxes(array $data): void{
    if (!$this->canEditStatus()){
      return;
    }
    
    $issues = new IssueTable(DB::get(), $this->getProject());
    $description = $issues->getIssueDescription($this->issue_id);
    
    $checked_indices = array_map(fn($i): int => (int)$i, $data[self::CHECKBOX_NAME] ?? []);
    $index = 0;
    
    $description = preg_replace_callback(IssueEditModel::TASK_REGEX, static function(array $matches) use ($checked_indices, &$index): string{
      return in_array(++$index, $checked_indices, true) ? '['.IssueEditModel::TASK_CHECKED_CHARS[0].']' : '[ ]';
    }, $description);
    
    if ($index > 0){
      $issues->updateIssueTasks($this->issue_id, $description, (int)floor(100.0 * count($checked_indices) / $index));
    }
  }
  
  public function getProgressUpdate(): array{
    $issues = new IssueTable(DB::get(), $this->getProject());
    $issue = $issues->getIssueDetail($this->issue_id);
    
    if ($issue === null){
      return [];
    }
    
    $status = $issue->getStatus();
    
    $active_milestone = $this->getActiveMilestone();
    $active_milestone_progress = $active_milestone === null ? null : $active_milestone->getPercentageDone();
    
    return [
        'issue_status'     => '<span class="'.$status->getTagClass().'"> '.$status->getTitle().'</span>',
        'issue_progress'   => $issue->getProgress(),
        'active_milestone' => $active_milestone_progress
    ];
  }
}

?>
