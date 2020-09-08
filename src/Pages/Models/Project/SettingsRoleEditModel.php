<?php
declare(strict_types = 1);

namespace Pages\Models\Project;

use Database\DB;
use Database\Objects\ProjectInfo;
use Database\Tables\ProjectRolePermTable;
use Database\Tables\ProjectRoleTable;
use Database\Validation\RoleFields;
use Exception;
use Pages\Components\Forms\Elements\FormCheckBoxHierarchyItem;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Text;
use Pages\IModel;
use Routing\Request;
use Session\Permissions\ProjectPermissions;
use Validation\FormValidator;
use Validation\ValidationException;

class SettingsRoleEditModel extends AbstractSettingsModel{
  public const ACTION_CONFIRM = 'Confirm';
  
  private static function addPerm(FormComponent $form, string $permission): FormCheckBoxHierarchyItem{
    return $form->addCheckBoxHierarchyItem(RoleFields::permissionFieldName($permission))->label(SettingsRolesModel::PERM_NAMES[$permission]);
  }
  
  private int $role_id;
  private ?string $role_title;
  
  private FormComponent $edit_form;
  
  public function __construct(Request $req, ProjectInfo $project, int $role_id){
    parent::__construct($req, $project);
    $this->role_id = $role_id;
    $this->role_title = (new ProjectRoleTable(DB::get(), $project))->getRoleTitleIfNotSpecial($role_id);
  }
  
  public function load(): IModel{
    parent::load();
    
    if ($this->role_title !== null){
      $form = $this->getEditForm();
      
      if (!$form->isFilled()){
        $fill = ['Title' => $this->role_title];
        
        foreach((new ProjectRolePermTable(DB::get(), $this->getProject()))->listRolePerms($this->role_id) as $perm){
          $fill[RoleFields::permissionFieldName($perm)] = true;
        }
        
        $form->fill($fill);
      }
    }
    
    return $this;
  }
  
  public function hasRole(): bool{
    return $this->role_title !== null;
  }
  
  public function getRoleTitleSafe(): string{
    return protect($this->role_title);
  }
  
  public function getEditForm(): FormComponent{
    if (isset($this->edit_form)){
      return $this->edit_form;
    }
    
    $form = new FormComponent(self::ACTION_CONFIRM);
    $form->addTextField('Title')->type('text');
    $form->startCheckBoxHierarchy('Permissions');
    
    self::addPerm($form, ProjectPermissions::VIEW_SETTINGS)
        ->description('View project settings.')
        ->parent();
    
    self::addPerm($form, ProjectPermissions::MANAGE_SETTINGS_GENERAL)
        ->description('Edit project name and visibility.')
        ->nonLastChild();
    
    self::addPerm($form, ProjectPermissions::MANAGE_SETTINGS_DESCRIPTION)
        ->description('Edit project description.')
        ->nonLastChild();
    
    self::addPerm($form, ProjectPermissions::MANAGE_SETTINGS_ROLES)
        ->description('Edit roles. Note this allows a member to edit all roles, including their own role and roles above theirs!') // TODO figure out a better system
        ->lastChild();
    
    self::addPerm($form, ProjectPermissions::LIST_MEMBERS)
        ->description('View all members of the project and their roles. Assign issues to members.')
        ->parent();
    
    self::addPerm($form, ProjectPermissions::MANAGE_MEMBERS)
        ->description('Invite members to the project, assign roles to members, remove members from the project. Can only invite and manage members of a lower role.')
        ->lastChild();
    
    self::addPerm($form, ProjectPermissions::MANAGE_MILESTONES)
        ->description('Create, edit, and delete milestones.');
    
    self::addPerm($form, ProjectPermissions::CREATE_ISSUE)
        ->description('Create new issues.');
    
    self::addPerm($form, ProjectPermissions::MODIFY_ALL_ISSUE_FIELDS)
        ->description('Note: Without this permission, a member can only edit the issue type, title, and description on issues they created, and all fields on issues they are assigned to.');
    
    self::addPerm($form, ProjectPermissions::EDIT_ALL_ISSUES)
        ->description('Note: Without this permission, a member can only edit issues they created or are assigned to.');
    
    self::addPerm($form, ProjectPermissions::DELETE_ALL_ISSUES)
        ->description('Note: Unlike editing, a member cannot delete an issue they created or are assigned to.');
    
    $form->endCheckBoxHierarchy();
    $form->addButton('submit', 'Edit Role')->icon('pencil');
    
    return $this->edit_form = $form;
  }
  
  public function editRole(array $data): bool{
    $form = $this->getEditForm();
    
    if (!$form->accept($data)){
      return false;
    }
    
    $validator = new FormValidator($data);
    $title = RoleFields::title($validator);
    $checked_perms = RoleFields::permissions($validator, SettingsRolesModel::PERM_NAMES, SettingsRolesModel::PERM_DEPENDENCIES);
    
    try{
      $validator->validate();
      $roles = new ProjectRoleTable(DB::get(), $this->getProject());
      $perms = new ProjectRolePermTable(DB::get(), $this->getProject());
      
      if (!$this->hasRole()){
        $form->addMessage(FormComponent::MESSAGE_ERROR, Text::blocked('Invalid role.'));
        return false;
      }
      
      if (($roles->getRoleIdByTitle($title) ?? $this->role_id) !== $this->role_id){
        $form->invalidateField('Title', 'A role with this title already exists.');
        return false;
      }
      
      $roles->editRole($this->role_id, $title);
      $perms->replaceRolePermissions($this->role_id, $checked_perms);
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
