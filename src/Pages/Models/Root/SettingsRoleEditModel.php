<?php
declare(strict_types = 1);

namespace Pages\Models\Root;

use Database\DB;
use Database\Tables\SystemRolePermTable;
use Database\Tables\SystemRoleTable;
use Database\Validation\RoleFields;
use Exception;
use Pages\Components\Forms\Elements\FormCheckBoxHierarchyItem;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Text;
use Pages\IModel;
use Routing\Request;
use Session\Permissions\SystemPermissions;
use Validation\FormValidator;
use Validation\ValidationException;

class SettingsRoleEditModel extends AbstractSettingsModel{
  public const ACTION_CONFIRM = 'Confirm';
  
  private static function addPermissionBox(FormComponent $form, string $permission): FormCheckBoxHierarchyItem{
    return $form->addCheckBoxHierarchyItem(RoleFields::permissionFieldName($permission))->label(SettingsRolesModel::PERM_NAMES[$permission]);
  }
  
  private int $role_id;
  private ?string $role_title;
  
  private FormComponent $edit_form;
  
  public function __construct(Request $req, int $role_id){
    parent::__construct($req);
    $this->role_id = $role_id;
    $this->role_title = (new SystemRoleTable(DB::get()))->getRoleTitleIfNotSpecial($role_id);
  }
  
  public function load(): IModel{
    parent::load();
    
    if ($this->role_title !== null){
      $form = $this->getEditForm();
      
      if (!$form->isFilled()){
        $fill = ['Title' => $this->role_title];
        
        foreach((new SystemRolePermTable(DB::get()))->listRolePerms($this->role_id) as $perm){
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
    
    self::addPermissionBox($form, SystemPermissions::MANAGE_SETTINGS)
        ->description('Full control over Lightning Tracker settings, including editing database credentials and user roles.');
    
    self::addPermissionBox($form, SystemPermissions::LIST_VISIBLE_PROJECTS)
        ->description('View all projects that are publicly visible, and projects the user has membership in.')
        ->parent();
    
    self::addPermissionBox($form, SystemPermissions::LIST_ALL_PROJECTS)
        ->description('View hidden projects as if you were a member.')
        ->nonLastChild();
    
    self::addPermissionBox($form, SystemPermissions::CREATE_PROJECT)
        ->description('Create new projects.')
        ->nonLastChild();
    
    self::addPermissionBox($form, SystemPermissions::MANAGE_PROJECTS)
        ->description('Full control over projects visible to the user. Includes project deletion.')
        ->lastChild();
    
    self::addPermissionBox($form, SystemPermissions::LIST_USERS)
        ->description('View names and roles of all registered users.')
        ->parent();
    
    self::addPermissionBox($form, SystemPermissions::SEE_USER_EMAILS)
        ->description('View emails of registered users.')
        ->nonLastChild();
    
    self::addPermissionBox($form, SystemPermissions::CREATE_USER)
        ->description('Register new users, bypassing \'Enable User Registration\' if disabled.')
        ->nonLastChild();
    
    self::addPermissionBox($form, SystemPermissions::MANAGE_USERS)
        ->description('Edit user account information, set user roles, delete users. Without the \'View User Emails\' permission, emails can be changed but cannot be seen.')
        ->lastChild();
    
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
      $roles = new SystemRoleTable(DB::get());
      $perms = new SystemRolePermTable(DB::get());
      
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
