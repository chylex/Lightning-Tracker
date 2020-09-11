<?php
declare(strict_types = 1);

namespace Pages\Models\Project;

use Database\DB;
use Database\Objects\ProjectInfo;
use Database\Objects\RoleInfo;
use Database\Tables\ProjectRolePermTable;
use Database\Tables\ProjectRoleTable;
use Database\Validation\RoleFields;
use Exception;
use Pages\Components\CompositeComponent;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Table\TableComponent;
use Pages\Components\Text;
use Routing\Link;
use Routing\Request;
use Session\Permissions\ProjectPermissions;
use Validation\FormValidator;
use Validation\ValidationException;

class SettingsRolesModel extends AbstractSettingsModel{
  public const ACTION_CREATE = 'Create';
  public const ACTION_MOVE = 'Move';
  public const ACTION_DELETE = 'Delete';
  
  private const ACTION_MOVE_UP = 'Up';
  private const ACTION_MOVE_DOWN = 'Down';
  
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
      ProjectPermissions::DELETE_ALL_ISSUES           => 'Delete All Issues'
  ];
  
  public const PERM_DEPENDENCIES = [
      ProjectPermissions::MANAGE_SETTINGS_GENERAL     => ProjectPermissions::VIEW_SETTINGS,
      ProjectPermissions::MANAGE_SETTINGS_DESCRIPTION => ProjectPermissions::VIEW_SETTINGS,
      ProjectPermissions::MANAGE_SETTINGS_ROLES       => ProjectPermissions::VIEW_SETTINGS,
      ProjectPermissions::MANAGE_MEMBERS              => ProjectPermissions::LIST_MEMBERS
  ];
  
  private ProjectPermissions $perms;
  
  private FormComponent $create_form;
  
  public function __construct(Request $req, ProjectInfo $project, ProjectPermissions $perms){
    parent::__construct($req, $project);
    $this->perms = $perms;
  }
  
  public function createRoleTable(): TableComponent{
    $table = new TableComponent();
    $table->ifEmpty('No roles found.');
    
    $table->addColumn('Title')->width(20)->bold();
    $table->addColumn('Permissions')->width(80)->wrap();
    
    if ($this->perms->check(ProjectPermissions::MANAGE_SETTINGS_ROLES)){
      $table->addColumn('Actions')->tight()->right();
    }
    
    $roles = new ProjectRoleTable(DB::get(), $this->getProject());
    $perms = new ProjectRolePermTable(DB::get(), $this->getProject());
    $ordering_limit = $roles->findMaxOrdering();
    
    foreach($roles->listRoles() as $role){
      $role_id = $role->getId();
      $role_id_str = (string)$role_id;
      
      $perm_list = implode(', ', array_map(fn($perm): string => self::PERM_NAMES[$perm], $perms->listRolePerms($role_id)));
      
      switch($role->getType()){
        case RoleInfo::PROJECT_OWNER:
          $perm_list_str = Text::missing('All');
          break;
        
        default:
          $perm_list_str = empty($perm_list) ? Text::missing('None') : $perm_list;
          break;
      }
      
      $row = [$role->getTitleSafe(), $perm_list_str];
      
      $can_edit = $this->perms->check(ProjectPermissions::MANAGE_SETTINGS_ROLES) && $role->getType() === RoleInfo::PROJECT_NORMAL;
      
      if ($this->perms->check(ProjectPermissions::MANAGE_SETTINGS_ROLES)){
        if ($can_edit){
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
      }
      
      $row = $table->addRow($row);
      
      if ($can_edit){
        $row->link(Link::fromBase($this->getReq(), 'settings', 'roles', $role_id_str));
      }
    }
    
    return $table;
  }
  
  public function getCreateForm(): ?FormComponent{
    if (!$this->perms->check(ProjectPermissions::MANAGE_SETTINGS_ROLES)){
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
    
    if (($button !== self::ACTION_MOVE_UP && $button !== self::ACTION_MOVE_DOWN) || $ordering === null){
      return false;
    }
    
    $roles = new ProjectRoleTable(DB::get(), $this->getProject());
    
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
    
    $roles = new ProjectRoleTable(DB::get(), $this->getProject());
    $roles->deleteById($role);
    return true;
  }
}

?>
