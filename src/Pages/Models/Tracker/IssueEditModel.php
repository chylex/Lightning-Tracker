<?php
declare(strict_types = 1);

namespace Pages\Models\Tracker;

use Database\DB;
use Database\Objects\IssueDetail;
use Database\Objects\TrackerInfo;
use Database\Objects\UserProfile;
use Database\Tables\IssueTable;
use Database\Tables\MilestoneTable;
use Database\Tables\TrackerMemberTable;
use Exception;
use Pages\Components\Forms\Elements\FormSelect;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Issues\AbstractIssueTag;
use Pages\Components\Issues\IssuePriority;
use Pages\Components\Issues\IssueScale;
use Pages\Components\Issues\IssueStatus;
use Pages\Components\Issues\IssueType;
use Pages\IModel;
use Pages\Models\BasicTrackerPageModel;
use Routing\Request;
use Session\Permissions;
use Validation\ValidationException;
use Validation\Validator;

class IssueEditModel extends BasicTrackerPageModel{
  /**
   * @param FormSelect $select
   * @param AbstractIssueTag[] $items
   */
  private static function setupIssueTagOptions(FormSelect $select, array $items): void{
    foreach($items as $item){
      $select->addOption($item->getId(), $item->getTitle(), 'issue-tag issue-'.$item->getKind().'-'.$item->getId());
    }
  }
  
  private Permissions $perms;
  private FormComponent $form;
  
  private ?int $issue_id;
  private ?IssueDetail $issue;
  
  /** @noinspection HtmlMissingClosingTag */
  public function __construct(Request $req, TrackerInfo $tracker, Permissions $perms, ?int $issue_id){
    parent::__construct($req, $tracker);
    
    $this->perms = $perms;
    $this->issue_id = $issue_id;
    
    if ($issue_id !== null){
      $issues = new IssueTable(DB::get(), $this->getTracker());
      $this->issue = $issues->getIssueDetail($this->issue_id);
    }
    else{
      $this->issue = null;
    }
    
    $this->form = new FormComponent();
    $this->form->addHTML(<<<HTML
<div class="split-wrapper split-collapse-640">
  <div class="split-75">
HTML
    );
    
    $this->form->startTitledSection('Details');
    $this->form->addTextField('Title');
    
    $this->form->startSplitGroup(33, 'issue-edit-triple-select');
    
    $select_type = $this->form->addSelect('Type')->optional();
    
    foreach(IssueType::list() as $type){
      $select_type->addOption($type->getId(), $type->getTitle(), 'icon icon-'.$type->getIcon());
    }
    
    self::setupIssueTagOptions($this->form->addSelect('Priority')->optional(), IssuePriority::list());
    self::setupIssueTagOptions($this->form->addSelect('Scale')->optional(), IssueScale::list());
    
    $this->form->endSplitGroup();
    
    $this->form->addTextArea('Description');
    
    $this->form->endTitledSection();
    
    $this->form->addHTML(<<<HTML
  </div>
  <div class="split-25 min-width-200 max-width-400">
HTML
    );
    
    $this->form->startTitledSection('Status');
    
    self::setupIssueTagOptions($this->form->addSelect('Status')->optional(), IssueStatus::list());
    
    $this->form->addNumberField('Progress', 0, 100)->step(5)->value('0');
    
    $select_milestone = $this->form->addSelect('Milestone')->addOption('', '(None)')->dropdown();
    $select_assignee = $this->form->addSelect('Assignee')->addOption('', '(None)')->dropdown();
    
    foreach((new MilestoneTable(DB::get(), $tracker))->listMilestones() as $milestone){
      $select_milestone->addOption(strval($milestone->getId()), $milestone->getTitleSafe());
    }
    
    if ($perms->checkTracker($tracker, MembersModel::PERM_LIST)){
      foreach((new TrackerMemberTable(DB::get(), $tracker))->listMembers() as $member){
        $select_assignee->addOption(strval($member->getUserId()), $member->getUserNameSafe());
      }
    }
    else{
      $select_assignee->disable();
      
      if ($this->issue !== null && $this->issue->getAssignee() !== null){
        $assignee = $this->issue->getAssignee();
        $select_assignee->addOption(strval($assignee->getId()), $assignee->getNameSafe());
      }
    }
    
    $this->form->endTitledSection();
    
    $this->form->addHTML(<<<HTML
  </div>
</div>
HTML
    );
    
    $this->form->startTitledSection('Confirm');
    $this->form->addButton('submit', $issue_id === null ? 'New Issue' : 'Edit Issue')->icon('pencil');
    $this->form->endTitledSection();
  }
  
  public function load(): IModel{
    parent::load();
    
    if (!$this->form->isFilled()){
      if ($this->issue_id === null){
        $this->form->fill(['Type'   => 'feature',
                           'Status' => 'open']);
      }
      elseif ($this->issue !== null){
        $issue = $this->issue;
        $milestone = $issue->getMilestoneId();
        $assignee = $issue->getAssignee();
        
        $this->form->fill(['Title'       => $issue->getTitleSafe(),
                           'Description' => $issue->getDescription()->getRawTextSafe(),
                           'Type'        => $issue->getType()->getId(),
                           'Priority'    => $issue->getPriority()->getId(),
                           'Scale'       => $issue->getScale()->getId(),
                           'Status'      => $issue->getStatus()->getId(),
                           'Progress'    => strval($issue->getProgress()),
                           'Milestone'   => $milestone === null ? '' : strval($milestone),
                           'Assignee'    => $assignee === null ? '' : strval($assignee->getId())]);
      }
    }
    
    return $this;
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
  
  public function createOrEditIssue(array $data, UserProfile $new_issue_author): ?int{
    if (!$this->form->accept($data)){
      return null;
    }
    
    $tracker = $this->getTracker();
    
    $title = $data['Title'];
    $description = $data['Description'];
    $type = $data['Type'] ?? '';
    $priority = $data['Priority'] ?? '';
    $scale = $data['Scale'] ?? '';
    $status = $data['Status'] ?? '';
    $progress = (int)$data['Progress'];
    $milestone = empty($data['Milestone']) ? null : (int)$data['Milestone'];
    $assignee = empty($data['Assignee']) ? null : (int)$data['Assignee'];
    
    $validator = new Validator();
    $validator->str('Title', $title)->notEmpty()->maxLength(128);
    $validator->str('Description', $description)->maxLength(65000);
    $validator->str('Type', $type)->isTrue(fn($v): bool => IssueType::exists($v), 'Type is invalid.');
    $validator->str('Priority', $priority)->isTrue(fn($v): bool => IssuePriority::exists($v), 'Priority is invalid.');
    $validator->str('Scale', $scale)->isTrue(fn($v): bool => IssueScale::exists($v), 'Scale is invalid.');
    $validator->str('Status', $status)->isTrue(fn($v): bool => IssueStatus::exists($v), 'Status is invalid.');
    $validator->int('Progress', $progress)->min(0)->max(100);
    
    $type = IssueType::get($type);
    $priority = IssuePriority::get($priority);
    $scale = IssueScale::get($scale);
    $status = IssueStatus::get($status);
    
    try{
      $validator->validate();
      $issues = new IssueTable(DB::get(), $tracker);
      
      if ($assignee !== null && !($assignee === $tracker->getOwnerId() || (new TrackerMemberTable(DB::get(), $tracker))->checkMembershipExists($assignee))){
        $real_assignee = $this->issue === null ? null : $this->issue->getAssignee();
        
        $this->form->invalidateField('Assignee', 'Assignee must be a member of the tracker.');
        $this->form->fill(['Assignee' => $real_assignee === null ? '' : strval($real_assignee->getId())]);
        return null;
      }
      
      if ($this->issue_id === null){
        $id = $issues->addIssue($new_issue_author, $title, $description, $type, $priority, $scale, $status, $progress, $milestone, $assignee);
      }
      else{
        if (!$this->perms->checkTracker($tracker, MembersModel::PERM_LIST)){
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
}

?>
