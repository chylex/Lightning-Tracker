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
  private bool $can_edit;
  private bool $has_member = false;
  private int $assigned_issue_count;
  
  private FormComponent $form;
  
  public function __construct(Request $req, ProjectInfo $project, UserId $member_user_id, UserId $logon_user_id){
    parent::__construct($req, $project);
    $this->member_user_id = $member_user_id;
    
    $db = DB::get();
    
    $this->form = new FormComponent(self::ACTION_CONFIRM);
    
    $users = new UserTable($db);
    $this->member_name = $users->getUserName($member_user_id);
    
    if ($this->member_name !== null){
      $members = new ProjectMemberTable($db, $project);
      $member_role = $members->getRoleIdStr($member_user_id);
      
      if ($member_role !== null){
        $user_id_str = $member_user_id->raw();
        
        $this->has_member = true;
        $this->can_edit = MemberEditModel::canEditMember($logon_user_id, $this->member_user_id, empty($member_role) ? null : (int)$member_role, $project);
        
        $issues = new IssueTable($db, $project);
        $filter = new IssueFilter();
        $filter->filterManual(['assignee' => [$user_id_str]]);
        $this->assigned_issue_count = $issues->countIssues($filter) ?? 0;
        
        $select_replacement = $this->form->addSelect('Reassign')
                                         ->addOption($user_id_str, '(Do Not Reassign)')
                                         ->addOption('', '(Reassign To Nobody)')
                                         ->dropdown();
        
        foreach($members->listMembers() as $member){
          $id = $member->getUserId();
          
          if (!$id->equals($member_user_id)){
            $select_replacement->addOption($id->raw(), $member->getUserName());
          }
        }
        
        $this->form->addButton('submit', 'Remove Member')->icon('trash');
      }
    }
  }
  
  public function load(): IModel{
    parent::load();
    
    if ($this->has_member && !$this->form->isFilled()){
      $this->form->fill(['Reassign' => $this->member_user_id->raw()]);
    }
    
    return $this;
  }
  
  public function canEdit(): bool{
    return $this->can_edit;
  }
  
  public function hasMember(): bool{
    return $this->has_member;
  }
  
  public function getMemberNameSafe(): string{
    return protect($this->member_name);
  }
  
  public function getAssignedIssueCount(): int{
    return $this->assigned_issue_count;
  }
  
  public function getRemoveForm(): FormComponent{
    return $this->form;
  }
  
  public function removeMemberSafely(): bool{
    if (!$this->has_member || $this->assigned_issue_count > 0){
      return false;
    }
    
    try{
      $members = new ProjectMemberTable(DB::get(), $this->getProject());
      $members->removeByUserId($this->member_user_id, false);
      return true;
    }catch(Exception $e){
      $this->form->onGeneralError($e);
    }
    
    return false;
  }
  
  public function removeMember(array $data): bool{
    if (!$this->form->accept($data)){
      return false;
    }
    
    $replacement = empty($data['Reassign']) ? null : UserId::fromRaw($data['Reassign']);
    
    try{
      $members = new ProjectMemberTable(DB::get(), $this->getProject());
      $members->removeByUserId($this->member_user_id, !$this->member_user_id->equals($replacement), $replacement);
      return true;
    }catch(Exception $e){
      $this->form->onGeneralError($e);
    }
    
    return false;
  }
}

?>
