<?php
declare(strict_types = 1);

namespace Pages\Models\Project;

use Data\CreateOrEditIssue;
use Data\IssuePriority;
use Data\IssueScale;
use Data\IssueStatus;
use Data\IssueType;
use Data\UserId;
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
  private ?string $new_issue_type = null;
  private ?int $issue_id = null;
  private ?IssueDetail $issue = null;
  private int $edit_level;
  
  private FormComponent $edit_form;
  
  public function __construct(Request $req, ProjectInfo $project, ProjectPermissions $perms, UserProfile $editor, CreateOrEditIssue $edit_request){
    parent::__construct($req, $project);
    $this->perms = $perms;
    $this->editor = $editor;
    
    if ($edit_request->isNewIssue()){
      $this->new_issue_type = $edit_request->getNewIssueType();
      $this->edit_level = $perms->check(ProjectPermissions::MODIFY_ALL_ISSUE_FIELDS) ? IssueDetail::EDIT_ALL_FIELDS : IssueDetail::EDIT_BASIC_FIELDS;
    }
    else{
      $this->issue_id = $edit_request->getIssueId();
      $this->issue = (new IssueTable(DB::get(), $project))->getIssueDetail($this->issue_id);
      $this->edit_level = $this->issue === null ? IssueDetail::EDIT_FORBIDDEN : $this->issue->getEditLevel($editor->getId(), $perms);
    }
  }
  
  public function load(): IModel{
    parent::load();
    
    $form = $this->getEditForm();
    
    if (!$form->isFilled()){
      if ($this->issue_id === null){
        $type = $this->new_issue_type === null || IssueType::get($this->new_issue_type) === null ? null : $this->new_issue_type;
        
        $form->fill(['Type'     => $type,
                     'Priority' => IssuePriority::MEDIUM,
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
    
    $this->getEditForm()->fill(['Title'       => $issue->getTitle(),
                                'Description' => $issue->getDescription()->getRawText(),
                                'Type'        => $issue->getType()->getId(),
                                'Priority'    => $issue->getPriority()->getId(),
                                'Scale'       => $issue->getScale()->getId(),
                                'Status'      => $issue->getStatus()->getId(),
                                'Progress'    => (string)$issue->getProgress(),
                                'Milestone'   => $milestone === null ? '' : (string)$milestone,
                                'Assignee'    => $assignee === null ? '' : $assignee->getId()->raw()]);
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
  
  public function getEditForm(): FormComponent{
    if (isset($this->edit_form)){
      return $this->edit_form;
    }
    
    $form = new FormComponent(self::ACTION_CONFIRM);
    $form->addHTML('<div class="split-wrapper split-collapse-1024 split-collapse-reversed">');
    
    $form->addHTML('<div class="split-25 min-width-200 max-width-250">');
    $form->startTitledSection('General');
    
    self::setupIssueTagOptions($form->addSelect('Type')->optional(), IssueType::list());
    
    if ($this->edit_level >= IssueDetail::EDIT_ALL_FIELDS){
      self::setupIssueTagOptions($form->addSelect('Priority')->optional(), IssuePriority::list());
      self::setupIssueTagOptions($form->addSelect('Scale')->optional(), IssueScale::list());
    }
    
    $form->endTitledSection();
    
    if ($this->edit_level >= IssueDetail::EDIT_ALL_FIELDS){
      $form->addHTML('</div><div class="split-50">');
    }
    else{
      $form->addHTML('</div><div class="split-75">');
    }
    
    $form->startTitledSection('Details');
    $form->addTextField('Title');
    $form->addLightMarkEditor('Description');
    $form->endTitledSection();
    
    if ($this->edit_level >= IssueDetail::EDIT_ALL_FIELDS){
      $form->addHTML('</div><div class="split-25 min-width-200 max-width-250">');
      $form->startTitledSection('Status');
      
      self::setupIssueTagOptions($form->addSelect('Status')->optional(), IssueStatus::list());
      
      $form->addNumberField('Progress', 0, 100)->step(5)->value('0');
      
      $select_milestone = $form->addSelect('Milestone')->addOption('', '(None)')->dropdown();
      $select_assignee = $form->addSelect('Assignee')->addOption('', '(None)')->dropdown();
      
      foreach((new MilestoneTable(DB::get(), $this->getProject()))->listMilestones() as $milestone){
        $select_milestone->addOption((string)$milestone->getMilestoneId(), $milestone->getTitle());
      }
      
      $issue_assignee = $this->issue === null ? null : $this->issue->getAssignee();
      $issue_assignee_id = $issue_assignee === null ? null : $issue_assignee->getId();
      
      if ($issue_assignee_id !== null){
        $select_assignee->addOption($issue_assignee_id->raw(), $issue_assignee->getName());
      }
      
      if ($this->perms->check(ProjectPermissions::LIST_MEMBERS)){
        foreach((new ProjectMemberTable(DB::get(), $this->getProject()))->listMembers() as $member){
          $id = $member->getUserId();
          
          if (!$id->equals($issue_assignee_id)){
            $select_assignee->addOption($id->raw(), $member->getUserName());
          }
        }
      }
      else{
        $select_assignee->disable();
      }
      
      $form->endTitledSection();
    }
    
    $form->addHTML('</div></div>');
    
    $form->startTitledSection('Confirm');
    $form->setMessagePlacementHere();
    $form->addButton('submit', $this->isNewIssue() ? 'Add Issue' : 'Edit Issue')->icon('checkmark');
    $form->endTitledSection();
    
    return $this->edit_form = $form;
  }
  
  public function createOrEditIssue(array $data): ?int{
    if (!$this->getEditForm()->accept($data)){
      return null;
    }
    
    switch($this->edit_level){
      case IssueDetail::EDIT_ALL_FIELDS:
        return $this->createOrEditIssueFull($data);
      
      case IssueDetail::EDIT_BASIC_FIELDS:
        return $this->createOrEditIssueLimited($data);
      
      default:
        return null;
    }
  }
  
  private function createOrEditIssueFull(array $data): ?int{
    $project = $this->getProject();
    $form = $this->getEditForm();
    
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
    
    $milestone = empty($data['Milestone']) ? null : UserId::fromRaw($data['Milestone']);
    $assignee = empty($data['Assignee']) ? null : UserId::fromRaw($data['Assignee']);
    
    try{
      $validator->validate();
      $issues = new IssueTable(DB::get(), $project);
      
      $prev_assignee = $this->issue === null ? null : $this->issue->getAssignee();
      $prev_assignee_id = $prev_assignee === null ? null : $prev_assignee->getId();
      
      if ($assignee !== null && !$assignee->equals($prev_assignee_id) && !(new ProjectMemberTable(DB::get(), $project))->checkMembershipExists($assignee)){
        $form->invalidateField('Assignee', 'Assignee must be a member of the project.');
        $form->fill(['Assignee' => $prev_assignee_id === null ? '' : $prev_assignee_id->raw()]);
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
              elseif (($progress > 0 && $prev_status === IssueStatus::OPEN) || $prev_status === IssueStatus::READY_TO_TEST){
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
      $form->invalidateFields($e->getFields());
    }catch(Exception $e){
      $form->onGeneralError($e);
    }
    
    return null;
  }
  
  private function createOrEditIssueLimited(array $data): ?int{
    $project = $this->getProject();
    $form = $this->getEditForm();
    
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
      $form->invalidateFields($e->getFields());
    }catch(Exception $e){
      $form->onGeneralError($e);
    }
    
    return null;
  }
}

?>
