<?php
declare(strict_types = 1);

namespace Pages\Models\Project;

use Database\DB;
use Database\Objects\ProjectInfo;
use Database\Objects\RoleInfo;
use Database\Objects\RoleManagementInfo;
use Database\Tables\ProjectRolePermTable;
use Database\Tables\ProjectRoleTable;
use Database\Validation\RoleFields;
use Exception;
use Pages\Components\Forms\FormComponent;
use Routing\Request;
use Session\Permissions\ProjectPermissions;
use Validation\FormValidator;
use Validation\ValidationException;

class SettingsRolesModel extends AbstractSettingsModel{
  public const ACTION_CREATE = 'Create';
  public const ACTION_MOVE = 'Move';
  public const ACTION_DELETE = 'Delete';
  
  public const BUTTON_MOVE_UP = 'Up';
  public const BUTTON_MOVE_DOWN = 'Down';
  
  public const PERM_NAMES = [
      ProjectPermissions::VIEW_SETTINGS               => 'View Settings',
      ProjectPermissions::MANAGE_SETTINGS_GENERAL     => 'Manage General Settings',
      ProjectPermissions::MANAGE_SETTINGS_DESCRIPTION => 'Manage Description',
      ProjectPermissions::MANAGE_SETTINGS_ROLES       => 'Manage Roles',
      ProjectPermissions::LIST_MEMBERS                => 'View Members',
      ProjectPermissions::MANAGE_MEMBERS              => 'Manage Members',
      ProjectPermissions::MANAGE_MILESTONES           => 'Manage Milestones',
      ProjectPermissions::CREATE_ISSUE                => 'Create Issues',
      ProjectPermissions::MODIFY_ALL_ISSUE_FIELDS     => 'Modify All Issue Fields',
      ProjectPermissions::EDIT_ALL_ISSUES             => 'Edit All Issues',
      ProjectPermissions::DELETE_ALL_ISSUES           => 'Delete All Issues',
  ];
  
  public const PERM_DEPENDENCIES = [
      ProjectPermissions::MANAGE_SETTINGS_GENERAL     => ProjectPermissions::VIEW_SETTINGS,
      ProjectPermissions::MANAGE_SETTINGS_DESCRIPTION => ProjectPermissions::VIEW_SETTINGS,
      ProjectPermissions::MANAGE_SETTINGS_ROLES       => ProjectPermissions::VIEW_SETTINGS,
      ProjectPermissions::MANAGE_MEMBERS              => ProjectPermissions::LIST_MEMBERS,
  ];
  
  private ProjectPermissions $perms;
  
  private FormComponent $create_form;
  
  public function __construct(Request $req, ProjectInfo $project, ProjectPermissions $perms){
    parent::__construct($req, $project);
    $this->perms = $perms;
  }
  
  public function canManageRoles(): bool{
    return $this->perms->check(ProjectPermissions::MANAGE_SETTINGS_ROLES);
  }
  
  public function canEditRole(RoleInfo $role): bool{
    return $this->canManageRoles() && $role->getType() === RoleInfo::PROJECT_NORMAL;
  }
  
  /**
   * @return RoleManagementInfo[]
   */
  public function getRoles(): array{
    $roles = new ProjectRoleTable(DB::get(), $this->getProject());
    $perms = new ProjectRolePermTable(DB::get(), $this->getProject());
    
    $ordering_limit = $roles->findMaxOrdering();
    return array_map(fn(RoleInfo $v): RoleManagementInfo => new RoleManagementInfo($v, $perms->listRolePerms($v->getId()), $ordering_limit), $roles->listRoles());
  }
  
  public function getCreateForm(): ?FormComponent{
    if (!$this->canManageRoles()){
      return null;
    }
    
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
    
    if ($form === null || !$form->accept($data)){
      return false;
    }
    
    $validator = new FormValidator($data);
    $title = RoleFields::title($validator);
    
    try{
      $validator->validate();
      $roles = new ProjectRoleTable(DB::get(), $this->getProject());
      
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
    
    $roles = new ProjectRoleTable(DB::get(), $this->getProject());
    
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
    
    $roles = new ProjectRoleTable(DB::get(), $this->getProject());
    $roles->deleteById($role);
    return true;
  }
}

?>
