<?php
declare(strict_types = 1);

namespace Pages\Models\Root;

use Database\DB;
use Database\Tables\SystemPermTable;
use Database\Validation\RoleFields;
use Exception;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Table\TableComponent;
use Pages\Components\Text;
use Pages\IModel;
use Routing\Link;
use Routing\Request;
use Session\Permissions\SystemPermissions;
use Validation\FormValidator;
use Validation\ValidationException;

class SettingsRolesModel extends AbstractSettingsModel{
  public const ACTION_CREATE = 'Create';
  public const ACTION_DELETE = 'Delete';
  
  public const PERM_NAMES = [
      SystemPermissions::MANAGE_SETTINGS       => 'Manage Settings',
      SystemPermissions::LIST_VISIBLE_PROJECTS => 'View Public Projects',
      SystemPermissions::LIST_ALL_PROJECTS     => 'View All Projects',
      SystemPermissions::CREATE_PROJECT        => 'Create Projects',
      SystemPermissions::MANAGE_PROJECTS       => 'Manage Projects',
      SystemPermissions::LIST_USERS            => 'View Users',
      SystemPermissions::SEE_USER_EMAILS       => 'View User Emails',
      SystemPermissions::CREATE_USER           => 'Create Users',
      SystemPermissions::MANAGE_USERS          => 'Manage Users'
  ];
  
  public const PERM_DEPENDENCIES = [
      SystemPermissions::LIST_ALL_PROJECTS => SystemPermissions::LIST_VISIBLE_PROJECTS,
      SystemPermissions::CREATE_PROJECT    => SystemPermissions::LIST_VISIBLE_PROJECTS,
      SystemPermissions::MANAGE_PROJECTS   => SystemPermissions::LIST_VISIBLE_PROJECTS,
      SystemPermissions::SEE_USER_EMAILS   => SystemPermissions::LIST_USERS,
      SystemPermissions::CREATE_USER       => SystemPermissions::LIST_USERS,
      SystemPermissions::MANAGE_USERS      => SystemPermissions::LIST_USERS
  ];
  
  private TableComponent $table;
  private FormComponent $form;
  
  public function __construct(Request $req){
    parent::__construct($req);
    
    $this->table = new TableComponent();
    $this->table->ifEmpty('No roles found.');
    $this->table->addColumn('Title')->width(20)->bold();
    $this->table->addColumn('Permissions')->width(80)->wrap();
    $this->table->addColumn('Actions')->tight()->right();
    
    $this->form = new FormComponent(self::ACTION_CREATE);
    $this->form->startTitledSection('Create Role');
    $this->form->setMessagePlacementHere();
    $this->form->addTextField('Title')->type('text');
    $this->form->addButton('submit', 'Create Role')->icon('pencil');
    $this->form->endTitledSection();
  }
  
  public function load(): IModel{
    parent::load();
    
    $perms = new SystemPermTable(DB::get());
    
    foreach($perms->listRoles() as $role){
      $role_id = $role->getId();
      $role_id_str = (string)$role_id;
      
      $perm_list = implode(', ', array_map(fn($perm): string => self::PERM_NAMES[$perm], $perms->listRolePerms($role_id)));
      $perm_list_str = $role->isSpecial() ? '<div class="center-text">-</div>' : (empty($perm_list) ? Text::missing('None') : $perm_list);
      
      $row = [$role->getTitleSafe(), $perm_list_str];
      
      if ($role->isSpecial()){
        $row[] = '';
      }
      else{
        $form_delete = new FormComponent(self::ACTION_DELETE);
        $form_delete->requireConfirmation('This action cannot be reversed. Do you want to continue?');
        $form_delete->addHidden('Role', $role_id_str);
        $form_delete->addIconButton('submit', 'circle-cross')->color('red');
        $row[] = $form_delete;
      }
      
      $row = $this->table->addRow($row);
      
      if (!$role->isSpecial()){
        $row->link(Link::fromBase($this->getReq(), 'settings', 'roles', $role_id_str));
      }
    }
    
    return $this;
  }
  
  public function getRoleTable(): TableComponent{
    return $this->table;
  }
  
  public function getCreateForm(): FormComponent{
    return $this->form;
  }
  
  public function createRole(array $data): bool{
    if (!$this->form->accept($data)){
      return false;
    }
    
    $validator = new FormValidator($data);
    $title = RoleFields::title($validator);
    
    try{
      $validator->validate();
      $perms = new SystemPermTable(DB::get());
      $perms->addRole($title, []);
      return true;
    }catch(ValidationException $e){
      $this->form->invalidateFields($e->getFields());
    }catch(Exception $e){
      $this->form->onGeneralError($e);
    }
    
    return false;
  }
  
  public function deleteRole(array $data): bool{ // TODO make it a dedicated page with additional checks
    $role = get_int($data, 'Role');
    
    if ($role === null){
      return false;
    }
    
    $perms = new SystemPermTable(DB::get());
    $perms->deleteById($role);
    return true;
  }
}

?>
