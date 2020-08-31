<?php
declare(strict_types = 1);

namespace Pages\Models\Root;

use Database\DB;
use Database\Filters\Types\UserFilter;
use Database\Tables\SystemPermTable;
use Database\Tables\UserTable;
use Database\Validation\UserFields;
use Exception;
use Pages\Components\DateTimeComponent;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Forms\IconButtonFormComponent;
use Pages\Components\Table\TableComponent;
use Pages\Components\Text;
use Pages\Models\BasicRootPageModel;
use Pages\Models\Mixed\RegisterModel;
use Routing\Link;
use Routing\Request;
use Session\Permissions\SystemPermissions;
use Session\Session;
use Validation\FormValidator;
use Validation\ValidationException;

class UsersModel extends BasicRootPageModel{
  public const ACTION_CREATE = 'Create';
  
  private SystemPermissions $perms;
  
  private FormComponent $create_form;
  
  public function __construct(Request $req, SystemPermissions $perms){
    parent::__construct($req);
    $this->perms = $perms;
  }
  
  public function createUserTable(): TableComponent{
    $req = $this->getReq();
    
    $logon_user_id = Session::get()->getLogonUserId();
    $can_see_email = $this->perms->check(SystemPermissions::SEE_USER_EMAILS);
    
    $table = new TableComponent();
    $table->ifEmpty('No users found.');
    
    if ($can_see_email){
      $table->addColumn('Username')->sort('name')->width(40)->wrap()->bold();
      $table->addColumn('Email')->width(40)->wrap();
    }
    else{
      $table->addColumn('Username')->sort('name')->width(80)->wrap()->bold();
    }
    
    $table->addColumn('Role')->sort('role_title')->width(20);
    $table->addColumn('Registration Time')->sort('date_registered')->tight()->right();
    
    if ($this->perms->check(SystemPermissions::MANAGE_USERS)){
      $table->addColumn('Actions')->tight()->right();
    }
    
    $filter = new UserFilter($can_see_email);
    $users = new UserTable(DB::get());
    
    $filtering = $filter->filter();
    $total_count = $users->countUsers($filter);
    $pagination = $filter->page($total_count);
    $sorting = $filter->sort($req);
    
    foreach($users->listUsers($filter) as $user){
      $user_id = $user->getId();
      $user_id_formatted = $user_id->formatted();
      
      $row = [$user->getNameSafe()];
      
      if ($can_see_email){
        $row[] = $user->getEmailSafe();
      }
      
      /** @noinspection ProperNullCoalescingOperatorUsageInspection */
      $row[] = $user->getRoleTitleSafe() ?? Text::missing('Default');
      $row[] = new DateTimeComponent($user->getRegistrationDate());
      
      if ($this->perms->check(SystemPermissions::MANAGE_USERS)){
        if ($user_id->equals($logon_user_id) || $user->isAdmin()){
          $row[] = '';
        }
        else{
          $link_delete = Link::fromBase($req, 'users', $user_id_formatted, 'delete');
          $btn_delete = new IconButtonFormComponent($link_delete, 'circle-cross');
          $btn_delete->color('red');
          $row[] = $btn_delete;
        }
      }
      
      $row = $table->addRow($row);
      
      if ($this->perms->check(SystemPermissions::MANAGE_USERS)){
        $row->link(Link::fromBase($req, 'users', $user_id_formatted));
      }
    }
    
    $table->setupColumnSorting($sorting);
    $table->setPaginationFooter($req, $pagination)->elementName('users');
    
    $header = $table->setFilteringHeader($filtering);
    $header->addTextField('name')->label('Username');
    
    if ($can_see_email){
      $header->addTextField('email')->label('Email');
    }
    
    $filtering_role = $header->addMultiSelect('role')->label('Role');
    $filtering_role->addOption('', Text::missing('Default'));
    
    foreach((new SystemPermTable(DB::get()))->listRoles() as $role){
      $title = $role->getTitle();
      $filtering_role->addOption($title, Text::plain($title));
    }
    
    return $table;
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
