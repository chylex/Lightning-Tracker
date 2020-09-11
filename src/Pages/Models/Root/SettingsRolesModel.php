<?php
declare(strict_types = 1);

namespace Pages\Models\Root;

use Database\DB;
use Database\Objects\RoleInfo;
use Database\Tables\SystemRolePermTable;
use Database\Tables\SystemRoleTable;
use Database\Validation\RoleFields;
use Exception;
use Pages\Components\CompositeComponent;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Table\TableComponent;
use Pages\Components\Text;
use Routing\Link;
use Session\Permissions\SystemPermissions;
use Validation\FormValidator;
use Validation\ValidationException;

class SettingsRolesModel extends AbstractSettingsModel{
  public const ACTION_CREATE = 'Create';
  public const ACTION_MOVE = 'Move';
  public const ACTION_DELETE = 'Delete';
  
  private const ACTION_MOVE_UP = 'Up';
  private const ACTION_MOVE_DOWN = 'Down';
  
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
  
  private FormComponent $create_form;
  
  public function createRoleTable(): TableComponent{
    $table = new TableComponent();
    $table->ifEmpty('No roles found.');
    
    $table->addColumn('Title')->width(20)->bold();
    $table->addColumn('Permissions')->width(80)->wrap();
    $table->addColumn('Actions')->tight()->right();
    
    $roles = new SystemRoleTable(DB::get());
    $perms = new SystemRolePermTable(DB::get());
    $ordering_limit = $roles->findMaxOrdering();
    
    foreach($roles->listRoles() as $role){
      $role_id = $role->getId();
      $role_id_str = (string)$role_id;
      
      $perm_list = implode(', ', array_map(fn($perm): string => self::PERM_NAMES[$perm], $perms->listRolePerms($role_id)));
      
      switch($role->getType()){
        case RoleInfo::SYSTEM_ADMIN:
          $perm_list_str = Text::missing('All');
          break;
        
        default:
          $perm_list_str = empty($perm_list) ? Text::missing('None') : $perm_list;
          break;
      }
      
      $row = [$role->getTitleSafe(), $perm_list_str];
      
      if ($role->getType() === RoleInfo::SYSTEM_NORMAL){
        $form_move = new FormComponent(self::ACTION_MOVE);
        $form_move->addHidden('Ordering', (string)$role->getOrdering());
  
        $btn_move_up = $form_move->addIconButton('submit', 'circle-up')->color('blue')->value(self::ACTION_MOVE_UP);
        $btn_move_down = $form_move->addIconButton('submit', 'circle-down')->color('blue')->value(self::ACTION_MOVE_DOWN);
  
        $ordering = $role->getOrdering();
  
        if ($ordering === 0 || $ordering === 1){
          $btn_move_up->disabled();
        }
  
        if ($ordering === 0 || $ordering === $ordering_limit){
          $btn_move_down->disabled();
        }
  
        $form_delete = new FormComponent(self::ACTION_DELETE);
        $form_delete->requireConfirmation('This action cannot be reversed. Do you want to continue?');
        $form_delete->addHidden('Role', $role_id_str);
        $form_delete->addIconButton('submit', 'circle-cross')->color('red');
  
        $row[] = new CompositeComponent($form_move, $form_delete);
      }
      else{
        $row[] = '';
      }
      
      $row = $table->addRow($row);
      
      if ($role->getType() === RoleInfo::SYSTEM_NORMAL){
        $row->link(Link::fromBase($this->getReq(), 'settings', 'roles', $role_id_str));
      }
    }
    
    return $table;
  }
  
  public function getCreateForm(): FormComponent{
    if (isset($this->create_form)){
      return $this->create_form;
    }
    
    $form = new FormComponent(self::ACTION_CREATE);
    $form->addTextField('Title')->type('text');
    $form->addButton('submit', 'Create Role')->icon('pencil');
    
    return $this->create_form = $form;
  }
  
  public function createRole(array $data): bool{
    $form = $this->getCreateForm();
    
    if (!$form->accept($data)){
      return false;
    }
    
    $validator = new FormValidator($data);
    $title = RoleFields::title($validator);
    
    try{
      $validator->validate();
      $roles = new SystemRoleTable(DB::get());
      
      if ($roles->getRoleIdByTitle($title) !== null){
        $form->invalidateField('Title', 'A role with this title already exists.');
        return false;
      }
      
      $roles->addRole($title);
      return true;
    }catch(ValidationException $e){
      $form->invalidateFields($e->getFields());
    }catch(Exception $e){
      $form->onGeneralError($e);
    }
    
    return false;
  }
  
  public function moveRole(array $data): bool{
    $button = $data[FormComponent::BUTTON_KEY] ?? null;
    $ordering = get_int($data, 'Ordering');
    
    if (($button !== self::ACTION_MOVE_UP && $button !== self::ACTION_MOVE_DOWN) || $ordering === null){
      return false;
    }
    
    $roles = new SystemRoleTable(DB::get());
    
    if ($button === self::ACTION_MOVE_UP){
      $roles->swapRolesIfNormal($ordering, $ordering - 1);
      return true;
    }
    elseif ($button === self::ACTION_MOVE_DOWN){
      $roles->swapRolesIfNormal($ordering, $ordering + 1);
      return true;
    }
    
    return false;
  }
  
  public function deleteRole(array $data): bool{ // TODO make it a dedicated page with additional checks
    $role = get_int($data, 'Role');
    
    if ($role === null){
      return false;
    }
    
    $roles = new SystemRoleTable(DB::get());
    $roles->deleteById($role);
    return true;
  }
}

?>
