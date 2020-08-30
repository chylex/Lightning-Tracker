<?php
declare(strict_types = 1);

namespace Pages\Models\Root;

use Database\DB;
use Database\Tables\SystemPermTable;
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
  
  private int $role_id;
  private ?string $role_title;
  
  /**
   * @var string[]
   */
  private array $all_perms;
  
  private FormComponent $form;
  
  public function __construct(Request $req, int $role_id){
    parent::__construct($req);
    $this->role_id = $role_id;
    
    $this->form = new FormComponent(self::ACTION_CONFIRM);
    $this->form->addTextField('Title')->type('text');
    $this->form->startCheckBoxHierarchy('Permissions');
    
    $this->addPermissionBox(SystemPermissions::MANAGE_SETTINGS)
         ->description('Full control over Lightning Tracker settings, including editing database credentials and user roles.');
    
    $this->addPermissionBox(SystemPermissions::LIST_VISIBLE_PROJECTS)
         ->description('View all projects that are publicly visible, and projects the user has membership in.')
         ->parent();
    
    $this->addPermissionBox(SystemPermissions::LIST_ALL_PROJECTS)
         ->description('View hidden projects as if you were a member.')
         ->nonLastChild();
    
    $this->addPermissionBox(SystemPermissions::CREATE_PROJECT)
         ->description('Create new projects.')
         ->nonLastChild();
    
    $this->addPermissionBox(SystemPermissions::MANAGE_PROJECTS)
         ->description('Delete projects.')
         ->lastChild();
    
    $this->addPermissionBox(SystemPermissions::LIST_USERS)
         ->description('View names and roles of all registered users.')
         ->parent();
    
    $this->addPermissionBox(SystemPermissions::SEE_USER_EMAILS)
         ->description('View emails of registered users.')
         ->nonLastChild();
    
    $this->addPermissionBox(SystemPermissions::CREATE_USER)
         ->description('Register new users, bypassing \'Enable User Registration\' if disabled.')
         ->nonLastChild();
    
    $this->addPermissionBox(SystemPermissions::MANAGE_USERS)
         ->description('Edit user account information, set user roles, delete users. Without the \'View User Emails\' permission, emails can be changed but cannot be seen.')
         ->lastChild();
    
    $this->form->endCheckBoxHierarchy();
    $this->form->addButton('submit', 'Edit Role')->icon('pencil');
  }
  
  private function addPermissionBox(string $permission): FormCheckBoxHierarchyItem{
    $this->all_perms[] = $permission;
    return $this->form->addCheckBoxHierarchyItem(RoleFields::permissionFieldName($permission))->label(SettingsRolesModel::PERM_NAMES[$permission]);
  }
  
  public function load(): IModel{
    parent::load();
    
    $perms = new SystemPermTable(DB::get());
    $this->role_title = $perms->getRoleTitleIfNotSpecial($this->role_id);
    
    if ($this->role_title !== null && !$this->form->isFilled()){
      $fill = ['Title' => $this->role_title];
      
      foreach($perms->listRolePerms($this->role_id) as $perm){
        $fill[RoleFields::permissionFieldName($perm)] = true;
      }
      
      $this->form->fill($fill);
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
    return $this->form;
  }
  
  public function editRole(array $data): bool{
    if (!$this->form->accept($data)){
      return false;
    }
    
    $validator = new FormValidator($data);
    $title = RoleFields::title($validator);
    $checked_perms = RoleFields::permissions($validator, SettingsRolesModel::PERM_NAMES, SettingsRolesModel::PERM_DEPENDENCIES, $this->all_perms);
    
    try{
      $validator->validate();
      $perms = new SystemPermTable(DB::get());
      
      if ($perms->getRoleTitleIfNotSpecial($this->role_id) === null){
        $this->form->addMessage(FormComponent::MESSAGE_ERROR, Text::blocked('Invalid role.'));
        return false;
      }
      
      $perms->editRole($this->role_id, $title, $checked_perms);
      return true;
    }catch(ValidationException $e){
      $this->form->invalidateFields($e->getFields());
    }catch(Exception $e){
      $this->form->onGeneralError($e);
    }
    
    return false;
  }
}

?>
