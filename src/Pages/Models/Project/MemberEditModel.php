<?php
declare(strict_types = 1);

namespace Pages\Models\Project;

use Database\DB;
use Database\Objects\ProjectInfo;
use Database\Tables\ProjectMemberTable;
use Database\Tables\ProjectPermTable;
use Database\Tables\UserTable;
use Exception;
use Pages\Components\Forms\FormComponent;
use Pages\Models\BasicProjectPageModel;
use Routing\Request;
use Validation\FormValidator;
use Validation\ValidationException;

class MemberEditModel extends BasicProjectPageModel{
  public const ACTION_CONFIRM = 'Confirm';
  
  public static function canEditMember(int $editor_id, int $target_id, ?int $target_role, ProjectInfo $project): bool{
    return (
        $target_id !== $editor_id &&
        $target_id !== $project->getOwnerId() &&
        ($target_role === null || (new ProjectPermTable(DB::get(), $project))->isRoleAssignableBy($target_role, $editor_id))
    );
  }
  
  private int $logon_user_id;
  private string $member_name;
  private string $member_role;
  private ?int $user_id;
  private bool $can_edit = false;
  private bool $has_member = false;
  
  private FormComponent $form;
  
  public function __construct(Request $req, ProjectInfo $project, string $member_name, int $logon_user_id){
    parent::__construct($req, $project);
    $this->member_name = $member_name;
    $this->logon_user_id = $logon_user_id;
    
    $this->form = new FormComponent(self::ACTION_CONFIRM);
    
    $db = DB::get();
    
    $users = new UserTable($db);
    $this->user_id = $users->findIdByName($member_name);
    
    if ($this->user_id === null){
      $member_role = null;
    }
    else{
      $member_role = (new ProjectMemberTable($db, $project))->getRoleIdStr($this->user_id);
    }
    
    if ($member_role !== null){
      $this->has_member = true;
      $this->member_role = $member_role;
      
      $this->can_edit = self::canEditMember($logon_user_id, $this->user_id, empty($member_role) ? null : (int)$member_role, $project);
      
      $select_role = $this->form->addSelect('Role')
                                ->dropdown()
                                ->addOption('', '(Default)');
      
      foreach((new ProjectPermTable($db, $project))->listRolesAssignableBy($logon_user_id) as $role){
        $select_role->addOption((string)$role->getId(), $role->getTitle());
      }
      
      $select_role->value($member_role);
    }
    
    $this->form->addButton('submit', 'Edit Member')->icon('pencil');
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
  
  public function getEditForm(): FormComponent{
    return $this->form;
  }
  
  public function editMember(array $data): bool{
    if (!$this->form->accept($data)){
      return false;
    }
    
    $db = DB::get();
    $project = $this->getProject();
    
    $validator = new FormValidator($data);
    $role_id = empty($data['Role']) ? null : (int)$data['Role'];
    
    try{
      $validator->validate();
      
      if ($role_id !== null && !(new ProjectPermTable($db, $project))->isRoleAssignableBy($role_id, $this->logon_user_id)){
        $this->form->fill(['Role' => $this->member_role]);
        $this->form->invalidateField('Role', 'Invalid role.');
        return false;
      }
      
      $members = new ProjectMemberTable($db, $project);
      $members->setRole($this->user_id, $role_id);
      return true;
    }catch(ValidationException $e){
      $this->form->invalidateFields($e->getFields());
    }catch(Exception $e){
      $this->form->onGeneralError($e);
    }
    
    return false;
  }
}

?>
