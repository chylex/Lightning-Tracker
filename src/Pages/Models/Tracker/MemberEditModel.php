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
use Pages\IModel;
use Pages\Models\BasicTrackerPageModel;
use Routing\Request;
use Session\Session;
use Validation\FormValidator;
use Validation\ValidationException;
use function Database\protect;

class MemberEditModel extends BasicTrackerPageModel{
  public const ACTION_EDIT = 'Edit';
  
  private string $member_name;
  private ?int $user_id;
  private bool $can_edit;
  private bool $has_member = false;
  
  private FormComponent $form;
  
  public function __construct(Request $req, TrackerInfo $tracker, string $member_name){
    parent::__construct($req, $tracker);
    $this->member_name = $member_name;
    
    $logon_user = Session::get()->getLogonUser();
    $logon_user_id = $logon_user === null ? null : $logon_user->getId();
    
    $users = new UserTable(DB::get());
    $this->user_id = $users->findIdByName($member_name);
    $this->can_edit = !($this->user_id === $logon_user_id || $this->user_id === $tracker->getOwnerId());
    
    $this->form = new FormComponent(self::ACTION_EDIT);
    
    $select_role = $this->form->addSelect('Role')
                              ->dropdown()
                              ->addOption('', '(Default)');
    
    foreach((new TrackerPermTable(DB::get(), $tracker))->listRoles() as $role){
      if (!$role->isSpecial()){
        $select_role->addOption(strval($role->getId()), $role->getTitle());
      }
    }
    
    $this->form->addButton('submit', 'Edit Member')->icon('pencil');
  }
  
  public function load(): IModel{
    parent::load();
    
    if (!$this->form->isFilled() && $this->user_id !== null){
      $members = new TrackerMemberTable(DB::get(), $this->getTracker());
      $role = $members->getRoleIdStr($this->user_id);
      
      if ($role !== null){
        $this->has_member = true;
        $this->form->fill(['Role' => $role]);
      }
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
    $role = empty($data['Role']) ? null : (int)$data['Role'];
    
    try{
      $validator->validate();
      
      if ($role !== null && (new TrackerPermTable($db, $tracker))->isRoleSpecial($role)){
        $this->form->invalidateField('Role', 'Invalid role.');
        return false;
      }
      
      $members = new TrackerMemberTable($db, $tracker);
      $members->setRole($this->user_id, $role);
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
