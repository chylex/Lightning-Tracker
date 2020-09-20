<?php
declare(strict_types = 1);

namespace Pages\Models\Root;

use Database\DB;
use Database\Objects\RoleInfo;
use Database\Objects\RoleManagementInfo;
use Database\Tables\SystemRolePermTable;
use Database\Tables\SystemRoleTable;
use Database\Validation\RoleFields;
use Exception;
use Pages\Components\Forms\FormComponent;
use Session\Permissions\SystemPermissions;
use Validation\FormValidator;
use Validation\ValidationException;

class SettingsRolesModel extends AbstractSettingsModel{
  public const ACTION_CREATE = 'Create';
  public const ACTION_MOVE = 'Move';
  public const ACTION_DELETE = 'Delete';
  
  private const BUTTON_MOVE_UP = 'Up';
  private const BUTTON_MOVE_DOWN = 'Down';
  
  public const PERM_NAMES = [
      SystemPermissions::MANAGE_SETTINGS       => 'Manage Settings',
      SystemPermissions::LIST_VISIBLE_PROJECTS => 'View Public Projects',
      SystemPermissions::LIST_ALL_PROJECTS     => 'View All Projects',
      SystemPermissions::CREATE_PROJECT        => 'Create Projects',
      SystemPermissions::MANAGE_PROJECTS       => 'Manage Projects',
      SystemPermissions::LIST_USERS            => 'View Users',
      SystemPermissions::SEE_USER_EMAILS       => 'View User Emails',
      SystemPermissions::CREATE_USER           => 'Create Users',
      SystemPermissions::MANAGE_USERS          => 'Manage Users',
  ];
  
  public const PERM_DEPENDENCIES = [
      SystemPermissions::LIST_ALL_PROJECTS => SystemPermissions::LIST_VISIBLE_PROJECTS,
      SystemPermissions::CREATE_PROJECT    => SystemPermissions::LIST_VISIBLE_PROJECTS,
      SystemPermissions::MANAGE_PROJECTS   => SystemPermissions::LIST_VISIBLE_PROJECTS,
      SystemPermissions::SEE_USER_EMAILS   => SystemPermissions::LIST_USERS,
      SystemPermissions::CREATE_USER       => SystemPermissions::LIST_USERS,
      SystemPermissions::MANAGE_USERS      => SystemPermissions::LIST_USERS,
  ];
  
  private FormComponent $create_form;
  
  public function canEditRole(RoleInfo $role): bool{
    return $role->getType() === RoleInfo::SYSTEM_NORMAL;
  }
  
  /**
   * @return RoleManagementInfo[]
   */
  public function getRoles(): array{
    $roles = new SystemRoleTable(DB::get());
    $perms = new SystemRolePermTable(DB::get());
    
    $ordering_limit = $roles->findMaxOrdering();
    return array_map(fn(RoleInfo $v): RoleManagementInfo => new RoleManagementInfo($v, $perms->listRolePerms($v->getId()), $ordering_limit), $roles->listRoles());
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
  
  public function createMoveForm(RoleManagementInfo $info): ?FormComponent{
    if (!$this->canEditRole($info->getRole())){
      return null;
    }
    
    $form = new FormComponent(self::ACTION_MOVE);
    $form->addHidden('Ordering', (string)$info->getRole()->getOrdering());
    
    $btn_move_up = $form->addIconButton('submit', 'circle-up')->color('blue')->value(self::BUTTON_MOVE_UP);
    $btn_move_down = $form->addIconButton('submit', 'circle-down')->color('blue')->value(self::BUTTON_MOVE_DOWN);
    
    if (!$info->canMoveUp()){
      $btn_move_up->disabled();
    }
    
    if (!$info->canMoveDown()){
      $btn_move_down->disabled();
    }
    
    return $form;
  }
  
  public function createDeleteForm(RoleInfo $role): ?FormComponent{
    if (!$this->canEditRole($role)){
      return null;
    }
    
    $form = new FormComponent(self::ACTION_DELETE);
    $form->requireConfirmation('This action cannot be reversed. Do you want to continue?');
    $form->addHidden('Role', (string)$role->getId());
    $form->addIconButton('submit', 'circle-cross')->color('red');
    
    return $form;
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
    
    if (($button !== self::BUTTON_MOVE_UP && $button !== self::BUTTON_MOVE_DOWN) || $ordering === null){
      return false;
    }
    
    $roles = new SystemRoleTable(DB::get());
    
    if ($button === self::BUTTON_MOVE_UP){
      $roles->swapRolesIfNormal($ordering, $ordering - 1);
      return true;
    }
    elseif ($button === self::BUTTON_MOVE_DOWN){
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
