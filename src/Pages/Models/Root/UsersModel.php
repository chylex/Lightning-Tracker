<?php
declare(strict_types = 1);

namespace Pages\Models\Root;

use Database\DB;
use Database\Filters\Types\UserFilter;
use Database\SQL;
use Database\Tables\SystemPermTable;
use Database\Tables\UserTable;
use Exception;
use Pages\Components\DateTimeComponent;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Table\TableComponent;
use Pages\IModel;
use Pages\Models\BasicRootPageModel;
use Pages\Models\Mixed\RegisterModel;
use PDOException;
use Routing\Request;
use Session\Permissions;
use Session\Session;
use Validation\ValidationException;

class UsersModel extends BasicRootPageModel{
  public const ACTION_CREATE = 'Create';
  public const ACTION_DELETE = 'Delete';
  
  public const PERM_LIST = 'users.list';
  public const PERM_LIST_EMAIL = 'users.list.email';
  public const PERM_ADD = 'users.add';
  public const PERM_EDIT = 'users.edit';
  
  private Permissions $perms;
  private TableComponent $table;
  private ?FormComponent $form;
  
  public function __construct(Request $req, Permissions $perms){
    parent::__construct($req);
    
    $this->perms = $perms;
    $this->perms->requireSystem(self::PERM_LIST);
    
    $this->table = new TableComponent();
    $this->table->ifEmpty('No users found.');
    
    
    if ($perms->checkSystem(self::PERM_LIST_EMAIL)){
      $this->table->addColumn('Username')->sort('name')->width(40)->bold();
      $this->table->addColumn('Email')->width(40);
    }
    else{
      $this->table->addColumn('Username')->sort('name')->width(80)->bold();
    }
    
    $this->table->addColumn('Role')->sort('role_title')->width(20);
    $this->table->addColumn('Registration Time')->sort('date_registered')->tight()->right();
    
    if ($perms->checkSystem(self::PERM_EDIT)){
      $this->table->addColumn('Actions')->tight()->right();
    }
    
    if ($perms->checkSystem(self::PERM_ADD)){
      $this->form = new FormComponent(self::ACTION_CREATE);
      $this->form->startTitledSection('Create User');
      $this->form->setMessagePlacementHere();
      
      $this->form->addTextField('Name')
                 ->label('Username')
                 ->type('text')
                 ->autocomplete('username');
      
      $this->form->addTextField('Password')
                 ->type('password')
                 ->autocomplete('new-password');
      
      $this->form->addTextField('Email')
                 ->type('email')
                 ->autocomplete('email');
      
      $this->form->addButton('submit', 'Create User')
                 ->icon('pencil');
      
      $this->form->endTitledSection();
    }
    else{
      $this->form = null;
    }
  }
  
  public function load(): IModel{
    parent::load();
    
    $logon_user = Session::get()->getLogonUser();
    $logon_user_id = $logon_user === null ? -1 : $logon_user->getId();
    
    $can_see_email = $this->perms->checkSystem(self::PERM_LIST_EMAIL);
    
    $filter = new UserFilter($can_see_email);
    $users = new UserTable(DB::get());
    
    $filtering = $filter->filter();
    $total_count = $users->countUsers($filter);
    $pagination = $filter->page($total_count);
    $sorting = $filter->sort($this->getReq());
    
    foreach($users->listUsers($filter) as $user){
      $user_id = $user->getId();
      $row = [$user->getNameSafe()];
      
      if ($can_see_email){
        $row[] = $user->getEmailSafe();
      }
      
      $row[] = $user->getRoleTitleSafe() ?? '<span class="missing">none</span>';
      $row[] = new DateTimeComponent($user->getRegistrationDate());
      
      if ($this->perms->checkSystem(self::PERM_EDIT)){
        if ($user_id === $logon_user_id){
          $row[] = '';
        }
        else{
          $form = new FormComponent(self::ACTION_DELETE);
          $form->requireConfirmation('This action cannot be reversed. Do you want to continue?');
          $form->addHidden('User', strval($user_id));
          $form->addIconButton('submit', 'circle-cross')->color('red');
          $row[] = $form;
        }
      }
      
      $this->table->addRow($row);
    }
    
    $this->table->setupColumnSorting($sorting);
    $this->table->setPaginationFooter($this->getReq(), $pagination)->elementName('users');
    
    $header = $this->table->setFilteringHeader($filtering);
    $header->addTextField('name')->label('Username');
    
    if ($can_see_email){
      $header->addTextField('email')->label('Email');
    }
    
    $filtering_role = $header->addMultiSelect('role')->label('Role');
    $filtering_role->addOption('', '<span class="missing">(None)</span>');
    
    foreach((new SystemPermTable(DB::get()))->listRoles() as $role){
      $title = $role->getTitleSafe();
      $filtering_role->addOption($title, $title);
    }
    
    return $this;
  }
  
  public function getUserTable(): TableComponent{
    return $this->table;
  }
  
  public function getCreateForm(): ?FormComponent{
    return $this->form;
  }
  
  public function createUser(array $data): bool{
    $this->perms->requireSystem(self::PERM_ADD);
    
    if (!$this->form->accept($data)){
      return false;
    }
    
    $name = $data['Name'];
    $email = $data['Email'];
    $password = $data['Password'];
    
    $validator = RegisterModel::validateUserFields($name, $email, $password);
    
    try{
      $validator->validate();
      $users = new UserTable(DB::get());
      $users->addUser($name, $email, $password);
      return true;
    }catch(ValidationException $e){
      $this->form->invalidateFields($e->getFields());
    }catch(PDOException $e){
      if ($e->getCode() === SQL::CONSTRAINT_VIOLATION && RegisterModel::checkDuplicateUser($this->form, $name, $email)){
        return false;
      }
      
      $this->form->onGeneralError($e);
    }catch(Exception $e){
      $this->form->onGeneralError($e);
    }
    
    return false;
  }
  
  public function deleteUser(array $data): bool{ // TODO make it a dedicated page with additional checks
    $this->perms->requireSystem(self::PERM_EDIT);
    
    if (!isset($data['User']) || !is_numeric($data['User'])){
      return false;
    }
    
    try{
      $users = new UserTable(DB::get());
      $users->deleteById((int)$data['User']);
      return true;
    }catch(PDOException $e){
      if ($e->getCode() === SQL::CONSTRAINT_VIOLATION){
        // TODO show message with reason which foreign key checks failed, i.e. cannot delete tracker owner
      }
      
      throw $e;
    }
  }
}

?>
