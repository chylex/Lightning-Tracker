<?php
declare(strict_types = 1);

namespace Pages\Models\Project;

use Data\UserId;
use Database\DB;
use Database\Objects\ProjectInfo;
use Database\Tables\ProjectMemberTable;
use Database\Tables\ProjectRoleTable;
use Database\Tables\UserTable;
use Exception;
use Pages\Components\Forms\FormComponent;
use Pages\Models\BasicProjectPageModel;
use Routing\Request;
use Validation\FormValidator;
use Validation\ValidationException;

class MemberEditModel extends BasicProjectPageModel{
  public const ACTION_CONFIRM = 'Confirm';
  
  public static function canEditMember(UserId $editor_id, UserId $target_id, ?int $target_role, ProjectInfo $project): bool{
    return (
        !$target_id->equals($editor_id) &&
        !$target_id->equals($project->getOwnerId()) &&
        ($target_role === null || (new ProjectRoleTable(DB::get(), $project))->isRoleAssignableBy($target_role, $editor_id))
    );
  }
  
  private UserId $member_user_id;
  private UserId $logon_user_id;
  private ?string $member_name;
  private ?string $member_role;
  private bool $can_edit = false;
  
  private FormComponent $edit_form;
  
  public function __construct(Request $req, ProjectInfo $project, UserId $member_user_id, UserId $logon_user_id){
    parent::__construct($req, $project);
    $this->member_user_id = $member_user_id;
    $this->logon_user_id = $logon_user_id;
    
    $db = DB::get();
    
    $this->member_name = (new UserTable($db))->getUserName($member_user_id);
    $this->member_role = $this->member_name === null ? null : (new ProjectMemberTable($db, $project))->getRoleIdStr($member_user_id);
    
    if ($this->member_role !== null){
      $this->can_edit = self::canEditMember($logon_user_id, $member_user_id, empty($this->member_role) ? null : (int)$this->member_role, $project);
    }
  }
  
  public function canEdit(): bool{
    return $this->can_edit;
  }
  
  public function hasMember(): bool{
    return $this->member_role !== null;
  }
  
  public function getMemberNameSafe(): string{
    return protect($this->member_name);
  }
  
  public function getEditForm(): FormComponent{
    if (isset($this->edit_form)){
      return $this->edit_form;
    }
    
    $form = new FormComponent(self::ACTION_CONFIRM);
    
    $select_role = $form->addSelect('Role')
                        ->dropdown()
                        ->addOption('', '(Default)');
    
    foreach((new ProjectRoleTable(DB::get(), $this->getProject()))->listRolesAssignableBy($this->logon_user_id) as $role){
      $select_role->addOption((string)$role->getId(), $role->getTitle());
    }
    
    $select_role->value($this->member_role);
    
    $form->addButton('submit', 'Edit Member')->icon('pencil');
    
    return $this->edit_form = $form;
  }
  
  public function editMember(array $data): bool{
    $form = $this->getEditForm();
    
    if (!$form->accept($data)){
      return false;
    }
    
    $db = DB::get();
    $project = $this->getProject();
    
    $validator = new FormValidator($data);
    $role_id = empty($data['Role']) ? null : (int)$data['Role'];
    
    try{
      $validator->validate();
      
      if ($role_id !== null && !(new ProjectRoleTable($db, $project))->isRoleAssignableBy($role_id, $this->logon_user_id)){
        $form->fill(['Role' => $this->member_role]);
        $form->invalidateField('Role', 'Invalid role.');
        return false;
      }
      
      $members = new ProjectMemberTable($db, $project);
      $members->setRole($this->member_user_id, $role_id);
      return true;
    }catch(ValidationException $e){
      $form->invalidateFields($e->getFields());
    }catch(Exception $e){
      $form->onGeneralError($e);
    }
    
    return false;
  }
}

?>
