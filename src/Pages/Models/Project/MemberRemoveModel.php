<?php
declare(strict_types = 1);

namespace Pages\Models\Project;

use Data\UserId;
use Database\DB;
use Database\Filters\Types\IssueFilter;
use Database\Objects\ProjectInfo;
use Database\Tables\IssueTable;
use Database\Tables\ProjectMemberTable;
use Database\Tables\UserTable;
use Exception;
use Pages\Components\Forms\FormComponent;
use Pages\IModel;
use Pages\Models\BasicProjectPageModel;
use Routing\Request;

class MemberRemoveModel extends BasicProjectPageModel{
  public const ACTION_CONFIRM = 'Confirm';
  
  private UserId $member_user_id;
  private ?string $member_name;
  private ?string $member_role;
  private bool $can_remove;
  private int $assigned_issue_count;
  
  private FormComponent $remove_form;
  
  public function __construct(Request $req, ProjectInfo $project, UserId $member_user_id, UserId $logon_user_id){
    parent::__construct($req, $project);
    $this->member_user_id = $member_user_id;
    
    $db = DB::get();
    
    $this->member_name = (new UserTable($db))->getUserName($member_user_id);
    $this->member_role = $this->member_name === null ? null : (new ProjectMemberTable($db, $project))->getRoleIdStr($member_user_id);
    
    if ($this->member_role !== null){
      $this->can_remove = MemberEditModel::canEditMember($logon_user_id, $member_user_id, empty($this->member_role) ? null : (int)$this->member_role, $project);
      
      $issues = new IssueTable($db, $project);
      $filter = new IssueFilter();
      $filter->filterManual(['assignee' => [$member_user_id->raw()]]);
      $this->assigned_issue_count = $issues->countIssues($filter) ?? 0;
    }
  }
  
  public function load(): IModel{
    parent::load();
    
    if ($this->hasMember()){
      $form = $this->getRemoveForm();
      
      if ($form->isFilled()){
        $form->fill(['Reassign' => $this->member_user_id->raw()]);
      }
    }
    
    return $this;
  }
  
  public function canRemove(): bool{
    return $this->can_remove;
  }
  
  public function hasMember(): bool{
    return $this->member_role !== null;
  }
  
  public function getMemberNameSafe(): string{
    return protect($this->member_name);
  }
  
  public function getAssignedIssueCount(): int{
    return $this->assigned_issue_count;
  }
  
  public function getRemoveForm(): FormComponent{
    if (isset($this->remove_form)){
      return $this->remove_form;
    }
    
    $form = new FormComponent(self::ACTION_CONFIRM);
    
    $select_replacement = $form->addSelect('Reassign')
                               ->addOption($this->member_user_id->raw(), '(Do Not Reassign)')
                               ->addOption('', '(Reassign To Nobody)')
                               ->dropdown();
    
    foreach((new ProjectMemberTable(DB::get(), $this->getProject()))->listMembers() as $member){
      $id = $member->getUserId();
      
      if (!$id->equals($this->member_user_id)){
        $select_replacement->addOption($id->raw(), $member->getUserName());
      }
    }
    
    $form->addButton('submit', 'Remove Member')->icon('trash');
    
    return $this->remove_form = $form;
  }
  
  public function removeMemberSafely(): bool{
    if (!$this->hasMember() || $this->assigned_issue_count > 0){
      return false;
    }
    
    try{
      $members = new ProjectMemberTable(DB::get(), $this->getProject());
      $members->removeByUserId($this->member_user_id, false);
      return true;
    }catch(Exception $e){
      $this->remove_form->onGeneralError($e);
    }
    
    return false;
  }
  
  public function removeMember(array $data): bool{
    $form = $this->getRemoveForm();
    
    if (!$form->accept($data)){
      return false;
    }
    
    $replacement = empty($data['Reassign']) ? null : UserId::fromRaw($data['Reassign']);
    
    try{
      $members = new ProjectMemberTable(DB::get(), $this->getProject());
      $members->removeByUserId($this->member_user_id, !$this->member_user_id->equals($replacement), $replacement);
      return true;
    }catch(Exception $e){
      $form->onGeneralError($e);
    }
    
    return false;
  }
}

?>
