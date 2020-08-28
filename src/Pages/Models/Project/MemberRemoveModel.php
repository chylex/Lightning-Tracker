<?php
declare(strict_types = 1);

namespace Pages\Models\Project;

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
  
  private int $logon_user_id;
  private string $member_name;
  private ?int $user_id;
  private bool $can_edit;
  private bool $has_member = false;
  private int $assigned_issue_count;
  
  private FormComponent $form;
  
  public function __construct(Request $req, ProjectInfo $project, string $member_name, int $logon_user_id){
    parent::__construct($req, $project);
    $this->member_name = $member_name;
    $this->logon_user_id = $logon_user_id;
    
    $this->form = new FormComponent(self::ACTION_CONFIRM);
    
    $db = DB::get();
    
    $users = new UserTable($db);
    $user_id = $users->findIdByName($member_name);
    $this->user_id = $user_id;
    
    if ($user_id !== null){
      $members = new ProjectMemberTable($db, $project);
      $member_role = $members->getRoleIdStr($user_id);
      
      if ($member_role !== null){
        $this->has_member = true;
        $this->can_edit = MemberEditModel::canEditMember($logon_user_id, $this->user_id, empty($member_role) ? null : (int)$member_role, $project);
        
        $issues = new IssueTable($db, $project);
        $filter = new IssueFilter();
        $filter->filterManual(['assignee' => [$user_id]]);
        $this->assigned_issue_count = $issues->countIssues($filter) ?? 0;
        
        $select_replacement = $this->form->addSelect('Reassign')
                                         ->addOption((string)$user_id, '(Do Not Reassign)')
                                         ->addOption('', '(Reassign To Nobody)')
                                         ->dropdown();
        
        foreach($members->listMembers() as $member){
          $id = $member->getUserId();
          
          if ($id !== $user_id){
            $select_replacement->addOption((string)$id, $member->getUserName());
          }
        }
        
        $this->form->addButton('submit', 'Remove Member')->icon('trash');
      }
    }
  }
  
  public function load(): IModel{
    parent::load();
    
    if ($this->has_member && !$this->form->isFilled()){
      $this->form->fill(['Reassign' => (string)$this->user_id]);
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
      $members->removeByUserId($this->user_id, false);
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
    
    $replacement = $data['Reassign'];
    
    if (empty($replacement)){
      $replacement = null;
    }
    elseif (is_numeric($replacement)){
      $replacement = (int)$replacement;
    }
    else{
      $this->form->invalidateField('Reassign', 'Invalid member.');
      return false;
    }
    
    try{
      $members = new ProjectMemberTable(DB::get(), $this->getProject());
      $members->removeByUserId($this->user_id, $replacement !== $this->user_id, $replacement);
      return true;
    }catch(Exception $e){
      $this->form->onGeneralError($e);
    }
    
    return false;
  }
}

?>
