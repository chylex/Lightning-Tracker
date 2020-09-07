<?php
declare(strict_types = 1);

namespace Pages\Models\Root;

use Data\UserId;
use Database\DB;
use Database\Objects\UserInfo;
use Database\Tables\SystemRoleTable;
use Database\Tables\UserTable;
use Database\Validation\UserFields;
use Exception;
use Pages\Components\Forms\FormComponent;
use Pages\IModel;
use Pages\Models\BasicRootPageModel;
use Pages\Models\Mixed\RegisterModel;
use Routing\Request;
use Session\Permissions\SystemPermissions;
use Validation\FormValidator;
use Validation\ValidationException;

class UserEditModel extends BasicRootPageModel{
  public const ACTION_CONFIRM = 'Confirm';
  
  private SystemPermissions $perms;
  private UserId $user_id;
  private ?UserInfo $user;
  
  private FormComponent $edit_form;
  
  public function __construct(Request $req, SystemPermissions $perms, UserId $user_id){
    parent::__construct($req);
    $this->perms = $perms;
    $this->user_id = $user_id;
    $this->user = (new UserTable(DB::get()))->getUserInfo($user_id);
  }
  
  public function load(): IModel{
    parent::load();
    
    if ($this->user !== null){
      $form = $this->getEditForm();
      
      if (!$form->isFilled()){
        $role_id = $this->user->getRoleId();
        
        $form->fill(['Name'  => $this->user->getName(),
                     'Email' => $this->perms->check(SystemPermissions::SEE_USER_EMAILS) ? $this->user->getEmail() : '',
                     'Role'  => $role_id === null ? '' : (string)$role_id]);
      }
    }
    
    return $this;
  }
  
  public function getUser(): ?UserInfo{
    return $this->user;
  }
  
  public function getEditForm(): FormComponent{
    if (isset($this->edit_form)){
      return $this->edit_form;
    }
    
    $form = new FormComponent(self::ACTION_CONFIRM);
    
    $form->startSplitGroup(50);
    
    $form->addTextField('Name')
         ->label('Username')
         ->type('text')
         ->autocomplete('username');
    
    if ($this->perms->check(SystemPermissions::SEE_USER_EMAILS)){
      $form->addTextField('Email')
           ->type('email')
           ->autocomplete('email');
    }
    else{
      $form->addTextField('Email')
           ->type('email')
           ->placeholder('Leave blank to keep current email.')
           ->autocomplete('email');
    }
    
    $form->endSplitGroup();
    $form->startSplitGroup(50);
    
    $form->addTextField('Password')
         ->label('Password')
         ->type('password')
         ->placeholder('Leave blank to keep current password.')
         ->autocomplete('new-password');
    
    $select_role = $form->addSelect('Role')
                        ->addOption('', '(None)')
                        ->dropdown();
    
    foreach((new SystemRoleTable(DB::get()))->listRoles() as $role){
      $select_role->addOption((string)$role->getId(), $role->getTitle());
    }
    
    $form->endSplitGroup();
    
    $form->addButton('submit', 'Edit User')->icon('pencil');
    
    return $this->edit_form = $form;
  }
  
  public function editUser(array $data): bool{
    $form = $this->getEditForm();
    
    if (!$form->accept($data)){
      return false;
    }
    
    $validator = new FormValidator($data);
    $name = UserFields::name($validator);
    $email = empty($data['Email']) ? null : UserFields::email($validator);
    $password = empty($data['Password']) ? null : UserFields::password($validator);
    $role = empty($data['Role']) ? null : (int)$data['Role'];
    
    try{
      $validator->validate();
      
      if (RegisterModel::checkDuplicateUser($form, $name, $email, $this->user_id)){
        return false;
      }
      
      $users = new UserTable(DB::get());
      $users->editUser($this->user_id, $name, $email, $password, $role);
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
