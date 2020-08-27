<?php
declare(strict_types = 1);

namespace Pages\Models\Project;

use Database\DB;
use Database\Objects\ProjectInfo;
use Database\Tables\ProjectPermTable;
use Database\Validation\RoleFields;
use Exception;
use Pages\Components\CompositeComponent;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Table\TableComponent;
use Pages\Components\Text;
use Pages\IModel;
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
      ProjectPermissions::MANAGE_SETTINGS         => 'Manage Settings',
      ProjectPermissions::LIST_MEMBERS            => 'View Members',
      ProjectPermissions::MANAGE_MEMBERS          => 'Manage Members',
      ProjectPermissions::MANAGE_MILESTONES       => 'Manage Milestones',
      ProjectPermissions::CREATE_ISSUE            => 'Create Issues',
      ProjectPermissions::MODIFY_ALL_ISSUE_FIELDS => 'Modify All Issue Fields',
      ProjectPermissions::EDIT_ALL_ISSUES         => 'Edit All Issues',
      ProjectPermissions::DELETE_ALL_ISSUES       => 'Delete All Issues'
  ];
  
  public const PERM_DEPENDENCIES = [
      ProjectPermissions::MANAGE_MEMBERS => ProjectPermissions::LIST_MEMBERS
  ];
  
  private TableComponent $table;
  private FormComponent $form;
  
  public function __construct(Request $req, ProjectInfo $project){
    parent::__construct($req, $project);
    
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
    
    $perms = new ProjectPermTable(DB::get(), $this->getProject());
    $ordering_limit = $perms->findMaxOrdering();
    
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
        $form_move = new FormComponent(self::ACTION_MOVE);
        $form_move->addHidden('Role', $role_id_str);
        
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
      $perms = new ProjectPermTable(DB::get(), $this->getProject());
      $perms->addRole($title, []);
      return true;
    }catch(ValidationException $e){
      $this->form->invalidateFields($e->getFields());
    }catch(Exception $e){
      $this->form->onGeneralError($e);
    }
    
    return false;
  }
  
  public function moveRole(array $data): bool{
    $button = $data[FormComponent::BUTTON_KEY] ?? null;
    $role = get_int($data, 'Role');
    
    if (($button !== self::ACTION_MOVE_UP && $button !== self::ACTION_MOVE_DOWN) || $role === null){
      return false;
    }
    
    $perms = new ProjectPermTable(DB::get(), $this->getProject());
    
    if ($button === self::ACTION_MOVE_UP){
      $perms->moveRoleUp($role);
      return true;
    }
    elseif ($button === self::ACTION_MOVE_DOWN){
      $perms->moveRoleDown($role);
      return true;
    }
    
    return false;
  }
  
  public function deleteRole(array $data): bool{ // TODO make it a dedicated page with additional checks
    $role = get_int($data, 'Role');
    
    if ($role === null){
      return false;
    }
    
    $perms = new ProjectPermTable(DB::get(), $this->getProject());
    $perms->deleteById($role);
    return true;
  }
}

?>