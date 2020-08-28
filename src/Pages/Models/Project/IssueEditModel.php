<?php
declare(strict_types = 1);

namespace Pages\Models\Project;

use Database\DB;
use Database\Objects\IssueDetail;
use Database\Objects\ProjectInfo;
use Database\Objects\UserProfile;
use Database\Tables\IssueTable;
use Database\Tables\MilestoneTable;
use Database\Tables\ProjectMemberTable;
use Database\Validation\IssueFields;
use Exception;
use Pages\Components\Forms\Elements\FormSelect;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Issues\IIssueTag;
use Pages\Components\Issues\IssuePriority;
use Pages\Components\Issues\IssueScale;
use Pages\Components\Issues\IssueStatus;
use Pages\Components\Issues\IssueType;
use Pages\IModel;
use Pages\Models\BasicProjectPageModel;
use Routing\Request;
use Session\Permissions\ProjectPermissions;
use Validation\FormValidator;
use Validation\ValidationException;

class IssueEditModel extends BasicProjectPageModel{
  public const ACTION_CONFIRM = 'Confirm';
  
  public const TASK_REGEX = '/^\[([ xX]?)]/mu';
  public const TASK_CHECKED_CHARS = ['x', 'X'];
  
  private static function calculateTaskProgress(string $description): ?int{
    $task_matches = [];
    $task_count = preg_match_all(self::TASK_REGEX, $description, $task_matches);
    
    if ($task_count > 0){
      $task_checked_count = 0;
      
      foreach($task_matches[1] as $match){
        if (in_array($match, self::TASK_CHECKED_CHARS, true)){
          ++$task_checked_count;
        }
      }
      
      return (int)floor(100.0 * $task_checked_count / $task_count);
    }
    else{
      return null;
    }
  }
  
  /**
   * @param FormSelect $select
   * @param IIssueTag[] $items
   */
  private static function setupIssueTagOptions(FormSelect $select, array $items): void{
    foreach($items as $item){
      $select->addOption($item->getId(), $item->getTitle(), $item->getTagClass());
    }
  }
  
  private ProjectPermissions $perms;
  private UserProfile $editor;
  private FormComponent $form;
  
  private ?int $issue_id;
  private ?IssueDetail $issue;
  private bool $can_edit_all_fields;
  
  public function __construct(Request $req, ProjectInfo $project, ProjectPermissions $perms, UserProfile $editor, ?int $issue_id){
    parent::__construct($req, $project);
    
    $this->perms = $perms;
    $this->editor = $editor;
    $this->issue_id = $issue_id;
    
    if ($issue_id !== null){
      $issues = new IssueTable(DB::get(), $this->getProject());
      $this->issue = $issues->getIssueDetail($this->issue_id);
      $this->can_edit_all_fields = $this->issue->getEditLevel($editor, $perms) >= IssueDetail::EDIT_ALL_FIELDS;
    }
    else{
      $this->issue = null;
      $this->can_edit_all_fields = $perms->check(ProjectPermissions::MODIFY_ALL_ISSUE_FIELDS);
    }
    
    $this->form = new FormComponent(self::ACTION_CONFIRM);
    $this->form->addHTML('<div class="split-wrapper split-collapse-1024 split-collapse-reversed">');
    
    if ($this->can_edit_all_fields){
      $this->form->addHTML('<div class="split-25 min-width-200 max-width-250">');
      $this->form->startTitledSection('Status');
      
      self::setupIssueTagOptions($this->form->addSelect('Status')->optional(), IssueStatus::list());
      
      $this->form->addNumberField('Progress', 0, 100)->step(5)->value('0');
      
      $select_milestone = $this->form->addSelect('Milestone')->addOption('', '(None)')->dropdown();
      $select_assignee = $this->form->addSelect('Assignee')->addOption('', '(None)')->dropdown();
      
      foreach((new MilestoneTable(DB::get(), $project))->listMilestones() as $milestone){
        $select_milestone->addOption((string)$milestone->getMilestoneId(), $milestone->getTitle());
      }
      
      $issue_assignee = $this->issue === null ? null : $this->issue->getAssignee();
      $issue_assignee_id = $issue_assignee === null ? null : $issue_assignee->getId();
      
      if ($issue_assignee !== null){
        $select_assignee->addOption((string)$issue_assignee_id, $issue_assignee->getName());
      }
      
      if ($perms->check(ProjectPermissions::LIST_MEMBERS)){
        foreach((new ProjectMemberTable(DB::get(), $project))->listMembers() as $member){
          $id = $member->getUserId();
          
          if ($id !== $issue_assignee_id){
            $select_assignee->addOption((string)$id, $member->getUserName());
          }
        }
      }
      else{
        $select_assignee->disable();
      }
      
      $this->form->endTitledSection();
      $this->form->addHTML('</div><div class="split-50">');
    }
    else{
      $this->form->addHTML('<div class="split-75">');
    }
    
    $this->form->startTitledSection('Details');
    $this->form->addTextField('Title');
    $this->form->addTextArea('Description')->markdownEditor();
    $this->form->endTitledSection();
    
    $this->form->addHTML('</div><div class="split-25 min-width-200 max-width-250">');
    $this->form->startTitledSection('General');
    
    self::setupIssueTagOptions($this->form->addSelect('Type')->optional(), IssueType::list());
    
    if ($this->can_edit_all_fields){
      self::setupIssueTagOptions($this->form->addSelect('Priority')->optional(), IssuePriority::list());
      self::setupIssueTagOptions($this->form->addSelect('Scale')->optional(), IssueScale::list());
    }
    
    $this->form->endTitledSection();
    $this->form->addHTML('</div></div>');
    
    $this->form->startTitledSection('Confirm');
    $this->form->setMessagePlacementHere();
    $this->form->addButton('submit', $issue_id === null ? 'Add Issue' : 'Edit Issue')->icon('checkmark');
    $this->form->endTitledSection();
  }
  
  public function load(): IModel{
    parent::load();
    
    if (!$this->form->isFilled()){
      if ($this->issue_id === null){
        $this->form->fill(['Priority' => IssuePriority::MEDIUM,
                           'Scale'    => IssueScale::MEDIUM,
                           'Status'   => IssueStatus::OPEN]);
      }
      else{
        $this->fillFormWithCurrentIssue();
      }
    }
    
    return $this;
  }
  
  private function fillFormWithCurrentIssue(): void{
    $issue = $this->issue;
    
    if ($issue === null){
      return;
    }
    
    $milestone = $issue->getMilestoneId();
    $assignee = $issue->getAssignee();
  
    $this->form->fill(['Title'       => $issue->getTitle(),
                       'Description' => $issue->getDescription()->getRawText(),
                       'Type'        => $issue->getType()->getId(),
                       'Priority'    => $issue->getPriority()->getId(),
                       'Scale'       => $issue->getScale()->getId(),
                       'Status'      => $issue->getStatus()->getId(),
                       'Progress'    => (string)$issue->getProgress(),
                       'Milestone'   => $milestone === null ? '' : (string)$milestone,
                       'Assignee'    => $assignee === null ? '' : (string)$assignee->getId()]);
  }
  
  public function isNewIssue(): bool{
    return $this->issue_id === null;
  }
  
  public function getIssue(): ?IssueDetail{
    return $this->issue;
  }
  
  public function getIssueId(): ?int{
    return $this->issue_id;
  }
  
  public function getForm(): FormComponent{
    return $this->form;
  }
  
  public function createOrEditIssue(array $data): ?int{
    if (!$this->form->accept($data)){
      return null;
    }
    
    if ($this->can_edit_all_fields){
      return $this->createOrEditIssueFull($data);
    }
    else{
      return $this->createOrEditIssueLimited($data);
    }
  }
  
  private function createOrEditIssueFull(array $data): ?int{
    $project = $this->getProject();
    
    $data['Type'] ??= '';
    $data['Priority'] ??= '';
    $data['Scale'] ??= '';
    $data['Status'] ??= '';
    
    $validator = new FormValidator($data);
    $title = IssueFields::title($validator);
    $description = IssueFields::description($validator);
    $type = IssueFields::type($validator);
    $priority = IssueFields::priority($validator);
    $scale = IssueFields::scale($validator);
    $status = IssueFields::status($validator);
    $progress = IssueFields::progress($validator);
    
    $milestone = empty($data['Milestone']) ? null : (int)$data['Milestone'];
    $assignee = empty($data['Assignee']) ? null : (int)$data['Assignee'];
    
    try{
      $validator->validate();
      $issues = new IssueTable(DB::get(), $project);
      
      $prev_assignee = $this->issue === null ? null : $this->issue->getAssignee();
      $prev_assignee_id = $prev_assignee === null ? null : $prev_assignee->getId();
      
      if ($assignee !== null && $assignee !== $prev_assignee_id && !(new ProjectMemberTable(DB::get(), $project))->checkMembershipExists($assignee)){
        $this->form->invalidateField('Assignee', 'Assignee must be a member of the project.');
        $this->form->fill(['Assignee' => $prev_assignee === null ? '' : (string)$prev_assignee_id]);
        $this->fillFormWithCurrentIssue();
        return null;
      }
      
      if ($this->issue_id === null){
        $id = $issues->addIssue($this->editor, $title, $description, $type, $priority, $scale, $status, $progress, $milestone, $assignee);
      }
      else{
        if ($progress === $this->issue->getProgress()){
          $prev_task_progress = self::calculateTaskProgress($this->issue->getDescription()->getRawText());
          $new_task_progress = self::calculateTaskProgress($description);
          
          if ($prev_task_progress !== $new_task_progress && $new_task_progress !== null){
            $progress = $new_task_progress;
            
            $prev_status = $this->issue->getStatus()->getId();
            $new_status = $status->getId();
            
            if ($prev_status === $new_status){ // same logic as in IssueTable (updateIssueTasks)
              if ($progress === 100){
                if ($prev_status === IssueStatus::OPEN || $prev_status === IssueStatus::IN_PROGRESS){
                  $status = IssueStatus::get(IssueStatus::READY_TO_TEST);
                }
              }
              elseif ($progress > 0 && $prev_status === IssueStatus::OPEN){
                $status = IssueStatus::get(IssueStatus::IN_PROGRESS);
              }
            }
          }
        }
        
        if (!$this->perms->check(ProjectPermissions::LIST_MEMBERS)){
          $prev_assignee = $this->issue === null ? null : $this->issue->getAssignee();
          $assignee = $prev_assignee === null ? null : $prev_assignee->getId();
        }
        
        $issues->editIssue($this->issue_id, $title, $description, $type, $priority, $scale, $status, $progress, $milestone, $assignee);
        $id = $this->issue_id;
      }
      
      return $id;
    }catch(ValidationException $e){
      $this->form->invalidateFields($e->getFields());
    }catch(Exception $e){
      $this->form->onGeneralError($e);
    }
    
    return null;
  }
  
  private function createOrEditIssueLimited(array $data): ?int{
    $project = $this->getProject();
    
    $data['Type'] ??= '';
    
    $validator = new FormValidator($data);
    $title = IssueFields::title($validator);
    $description = IssueFields::description($validator);
    $type = IssueFields::type($validator);
    
    try{
      $validator->validate();
      $issues = new IssueTable(DB::get(), $project);
      
      if ($this->issue_id === null){
        $priority = IssuePriority::get(IssuePriority::MEDIUM);
        $scale = IssueScale::get(IssueScale::MEDIUM);
        $status = IssueStatus::get(IssueStatus::OPEN);
        $id = $issues->addIssue($this->editor, $title, $description, $type, $priority, $scale, $status, 0, null, null);
      }
      else{
        $issues->editIssueLimited($this->issue_id, $title, $description, $type);
        $id = $this->issue_id;
      }
      
      return $id;
    }catch(ValidationException $e){
      $this->form->invalidateFields($e->getFields());
    }catch(Exception $e){
      $this->form->onGeneralError($e);
    }
    
    return null;
  }
}

?>
