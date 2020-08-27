<?php
declare(strict_types = 1);

namespace Pages\Models\Root;

use Database\DB;
use Database\Objects\UserInfo;
use Database\SQL;
use Database\Tables\SystemPermTable;
use Database\Tables\UserTable;
use Database\Validation\UserFields;
use Exception;
use Pages\Components\Forms\FormComponent;
use Pages\IModel;
use Pages\Models\BasicRootPageModel;
use Pages\Models\Mixed\RegisterModel;
use PDOException;
use Routing\Request;
use Session\Permissions\SystemPermissions;
use Validation\FormValidator;
use Validation\ValidationException;

class UserEditModel extends BasicRootPageModel{
  public const ACTION_CONFIRM = 'Confirm';
  
  private int $user_id;
  private ?UserInfo $user;
  
  private SystemPermissions $perms;
  private FormComponent $form;
  
  public function __construct(Request $req, SystemPermissions $perms, int $user_id){
    parent::__construct($req);
    
    $this->perms = $perms;
    $this->user_id = $user_id;
    
    $this->form = new FormComponent(self::ACTION_CONFIRM);
    
    $this->form->startSplitGroup(50);
    
    $this->form->addTextField('Name')
               ->label('Username')
               ->type('text')
               ->autocomplete('username');
    
    if ($perms->check(SystemPermissions::SEE_USER_EMAILS)){
      $this->form->addTextField('Email')
                 ->type('email')
                 ->autocomplete('email');
    }
    else{
      $this->form->addTextField('Email')
                 ->type('email')
                 ->placeholder('Leave blank to keep current email.')
                 ->autocomplete('email');
    }
    
    $this->form->endSplitGroup();
    $this->form->startSplitGroup(50);
    
    $this->form->addTextField('Password')
               ->label('Password')
               ->type('password')
               ->placeholder('Leave blank to keep current password.')
               ->autocomplete('new-password');
    
    $select_role = $this->form->addSelect('Role')
                              ->addOption('', '(None)')
                              ->dropdown();
    
    foreach((new SystemPermTable(DB::get()))->listRoles() as $role){
      $select_role->addOption(strval($role->getId()), $role->getTitle());
    }
    
    $this->form->endSplitGroup();
    
    $this->form->addButton('submit', 'Edit User')->icon('pencil');
  }
  
  public function load(): IModel{
    parent::load();
    
    $users = new UserTable(DB::get());
    $user = $users->getUserInfo($this->user_id);
    $this->user = $user;
    
    if ($user !== null && !$this->form->isFilled()){
      $role_id = $user->getRoleId();
      
      $this->form->fill(['Name'  => $user->getName(),
                         'Email' => $this->perms->check(SystemPermissions::SEE_USER_EMAILS) ? $user->getEmail() : '',
                         'Role'  => $role_id === null ? '' : strval($role_id)]);
    }
    
    return $this;
  }
  
  public function getUser(): ?UserInfo{
    return $this->user;
  }
  
  public function getEditForm(): FormComponent{
    return $this->form;
  }
  
  public function editUser(array $data): bool{
    if (!$this->form->accept($data)){
      return false;
    }
    
    $validator = new FormValidator($data);
    $name = UserFields::name($validator);
    $email = empty($data['Email']) ? null : UserFields::email($validator);
    $password = empty($data['Password']) ? null : UserFields::password($validator);
    $role = empty($data['Role']) ? null : (int)$data['Role'];
    
    try{
      $validator->validate();
      $users = new UserTable(DB::get());
      $users->editUser($this->user_id, $name, $email, $password, $role);
      return true;
    }catch(ValidationException $e){
      $this->form->invalidateFields($e->getFields());
    }catch(PDOException $e){
      if ($e->getCode() === SQL::CONSTRAINT_VIOLATION && RegisterModel::checkDuplicateUser($this->form, $name, $email, $this->user_id)){
        return false;
      }
      
      $this->form->onGeneralError($e);
    }catch(Exception $e){
      $this->form->onGeneralError($e);
    }
    
    return false;
  }
}

?>
