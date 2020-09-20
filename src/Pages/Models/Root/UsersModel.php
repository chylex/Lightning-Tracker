<?php
declare(strict_types = 1);

namespace Pages\Models\Root;

use Database\DB;
use Database\Filters\Types\UserFilter;
use Database\Objects\UserInfo;
use Database\Tables\SystemRoleTable;
use Database\Tables\UserTable;
use Database\Validation\UserFields;
use Exception;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Table\TableComponent;
use Pages\Components\Text;
use Pages\Models\BasicRootPageModel;
use Pages\Models\Mixed\RegisterModel;
use Routing\Request;
use Session\Permissions\SystemPermissions;
use Session\Session;
use Validation\FormValidator;
use Validation\ValidationException;

class UsersModel extends BasicRootPageModel{
  public const ACTION_CREATE = 'Create';
  
  private SystemPermissions $perms;
  
  private FormComponent $create_form;
  
  /**
   * @var string[]
   */
  private array $editable_roles = [];
  
  public function __construct(Request $req, SystemPermissions $perms){
    parent::__construct($req);
    $this->perms = $perms;
    
    if ($perms->check(SystemPermissions::MANAGE_USERS)){
      $logon_user_id = Session::get()->getLogonUserId();
      
      if ($logon_user_id !== null){
        foreach((new SystemRoleTable(DB::get()))->listRolesAssignableBy($logon_user_id) as $role){
          $this->editable_roles[$role->getId()] = $role->getTitle();
        }
      }
    }
  }
  
  public function canManageUsers(): bool{
    return $this->perms->check(SystemPermissions::MANAGE_USERS);
  }
  
  public function canSeeEmail(): bool{
    return $this->perms->check(SystemPermissions::SEE_USER_EMAILS);
  }
  
  public function canEditUser(UserInfo $user): bool{
    return (
        $this->canManageUsers() &&
        !$user->getId()->equals(Session::get()->getLogonUserId()) &&
        ($user->getRoleId() === null || array_key_exists($user->getRoleId(), $this->editable_roles))
    );
  }
  
  public function setupUserTableFilter(TableComponent $table): UserFilter{
    $req = $this->getReq();
    $can_see_email = $this->canSeeEmail();
    
    $filter = new UserFilter($can_see_email);
    $users = new UserTable(DB::get());
    
    $filtering = $filter->filter();
    $total_count = $users->countUsers($filter);
    $pagination = $filter->page($total_count);
    $sorting = $filter->sort($req);
    
    $table->setupColumnSorting($sorting);
    $table->setPaginationFooter($req, $pagination)->elementName('users');
    
    $header = $table->setFilteringHeader($filtering);
    $header->addTextField('name')->label('Username');
    
    if ($can_see_email){
      $header->addTextField('email')->label('Email');
    }
    
    $filtering_role = $header->addMultiSelect('role')->label('Role');
    $filtering_role->addOption('', Text::missing('Default'));
    
    foreach((new SystemRoleTable(DB::get()))->listRoles() as $role){
      $title = $role->getTitle();
      $filtering_role->addOption($title, Text::plain($title));
    }
    
    return $filter;
  }
  
  /**
   * @param UserFilter $filter
   * @return UserInfo[]
   */
  public function getUserList(UserFilter $filter): array{
    return (new UserTable(DB::get()))->listUsers($filter);
  }
  
  public function getCreateForm(): ?FormComponent{
    if (!$this->perms->check(SystemPermissions::CREATE_USER)){
      return null;
    }
    
    if (isset($this->create_form)){
      return $this->create_form;
    }
    
    $form = new FormComponent(self::ACTION_CREATE);
    
    $form->addTextField('Name')
         ->label('Username')
         ->type('text')
         ->autocomplete('username');
    
    $form->addTextField('Password')
         ->type('password')
         ->autocomplete('new-password');
    
    $form->addTextField('Email')
         ->type('email')
         ->autocomplete('email');
    
    $form->addButton('submit', 'Create User')
         ->icon('pencil');
    
    return $this->create_form = $form;
  }
  
  public function createUser(array $data): bool{
    $form = $this->getCreateForm();
    
    if ($form === null || !$form->accept($data)){
      return false;
    }
    
    $validator = new FormValidator($data);
    $name = UserFields::name($validator);
    $email = UserFields::email($validator);
    $password = UserFields::password($validator);
    
    try{
      $validator->validate();
      
      if (RegisterModel::checkDuplicateUser($form, $name, $email)){
        return false;
      }
      
      $users = new UserTable(DB::get());
      $users->addUser($name, $email, $password);
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
