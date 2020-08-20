<?php
declare(strict_types = 1);

namespace Pages\Models\Tracker;

use Database\DB;
use Database\Objects\TrackerInfo;
use Database\Tables\TrackerMemberTable;
use Database\Tables\TrackerPermTable;
use Database\Tables\UserTable;
use Exception;
use Pages\Components\Forms\FormComponent;
use Pages\Models\BasicTrackerPageModel;
use Routing\Request;
use Validation\FormValidator;
use Validation\ValidationException;
use function Database\protect;

class MemberEditModel extends BasicTrackerPageModel{
  public const ACTION_EDIT = 'Edit';
  
  public static function canEditMember(int $editor_id, int $target_id, ?int $target_role, TrackerInfo $tracker): bool{
    return (
        $target_id !== $editor_id &&
        $target_id !== $tracker->getOwnerId() &&
        ($target_role === null || (new TrackerPermTable(DB::get(), $tracker))->isRoleAssignableBy($target_role, $editor_id))
    );
  }
  
  private int $logon_user_id;
  private string $member_name;
  private string $member_role;
  private ?int $user_id;
  private bool $can_edit;
  private bool $has_member = false;
  
  private FormComponent $form;
  
  public function __construct(Request $req, TrackerInfo $tracker, string $member_name, int $logon_user_id){
    parent::__construct($req, $tracker);
    $this->member_name = $member_name;
    $this->logon_user_id = $logon_user_id;
    
    $this->form = new FormComponent(self::ACTION_EDIT);
    
    $db = DB::get();
    
    $users = new UserTable($db);
    $this->user_id = $users->findIdByName($member_name);
    
    $perms = new TrackerPermTable($db, $tracker);
    $members = new TrackerMemberTable($db, $tracker);
    $member_role = $members->getRoleIdStr($this->user_id);
    
    if ($member_role !== null){
      $this->has_member = true;
      $this->member_role = $member_role;
      
      $this->can_edit = self::canEditMember($logon_user_id, $this->user_id, empty($member_role) ? null : intval($member_role), $tracker);
      
      $select_role = $this->form->addSelect('Role')
                                ->dropdown()
                                ->addOption('', '(Default)');
      
      foreach($perms->listRolesAssignableBy($logon_user_id) as $role){
        $select_role->addOption(strval($role->getId()), $role->getTitle());
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
    $tracker = $this->getTracker();
    
    $validator = new FormValidator($data);
    $role_id = empty($data['Role']) ? null : (int)$data['Role'];
    
    try{
      $validator->validate();
      
      if ($role_id !== null && !(new TrackerPermTable($db, $tracker))->isRoleAssignableBy($role_id, $this->logon_user_id)){
        $this->form->fill(['Role' => $this->member_role]);
        $this->form->invalidateField('Role', 'Invalid role.');
        return false;
      }
      
      $members = new TrackerMemberTable($db, $tracker);
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
