<?php
declare(strict_types = 1);

namespace Pages\Models\Tracker;

use Database\DB;
use Database\Objects\TrackerInfo;
use Database\Tables\TrackerPermTable;
use Database\Validation\RoleFields;
use Exception;
use Pages\Components\Forms\Elements\FormCheckBoxHierarchyItem;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Text;
use Pages\IModel;
use Routing\Request;
use Validation\FormValidator;
use Validation\ValidationException;

class SettingsRoleEditModel extends AbstractSettingsModel{
  public const ACTION_CONFIRM = 'Confirm';
  
  private static function perm(string $permission): string{
    return 'Perm-'.str_replace('.', '_', $permission);
  }
  
  private int $role_id;
  private ?string $role_title;
  
  /**
   * @var string[]
   */
  private array $all_perms;
  
  private FormComponent $form;
  
  public function __construct(Request $req, TrackerInfo $tracker, int $role_id){
    parent::__construct($req, $tracker);
    $this->role_id = $role_id;
    
    $this->form = new FormComponent(self::ACTION_CONFIRM);
    $this->form->addTextField('Title')->type('text');
    $this->form->startCheckBoxHierarchy('Permissions');
    
    $this->addPermissionBox(AbstractSettingsModel::PERM)
         ->description('Full control over the tracker Settings, including editing all roles.');
    
    $this->addPermissionBox(MembersModel::PERM_LIST)
         ->description('View all members of the tracker and their roles. Assign issues to members.')
         ->parent();
    
    $this->addPermissionBox(MembersModel::PERM_MANAGE)
         ->description('Invite members to the tracker, assign roles to members, remove members from the tracker. Can only invite and manage members of a lower role.')
         ->lastChild();
    
    $this->addPermissionBox(MilestonesModel::PERM_MANAGE)
         ->description('Create, edit, and delete milestones.');
    
    $this->addPermissionBox(IssuesModel::PERM_CREATE)
         ->description('Create new issues.');
    
    $this->addPermissionBox(IssuesModel::PERM_EDIT_ALL)
         ->description('Note: Without this permission, a member can only edit issues they created or are assigned to.');
    
    $this->addPermissionBox(IssuesModel::PERM_DELETE_ALL)
         ->description('Note: Unlike editing, a member cannot delete an issue they created or are assigned to.');
    
    $this->form->endCheckBoxHierarchy();
    $this->form->addButton('submit', 'Edit Role')->icon('pencil');
  }
  
  private function addPermissionBox(string $permission): FormCheckBoxHierarchyItem{
    $this->all_perms[] = $permission;
    return $this->form->addCheckBoxHierarchyItem(self::perm($permission))->label(SettingsRolesModel::PERM_NAMES[$permission]);
  }
  
  public function load(): IModel{
    parent::load();
    
    $perms = new TrackerPermTable(DB::get(), $this->getTracker());
    $this->role_title = $perms->getRoleTitleIfNotSpecial($this->role_id);
    
    if ($this->role_title !== null && !$this->form->isFilled()){
      $fill = ['Title' => $this->role_title];
      
      foreach($perms->listRolePerms($this->role_id) as $perm){
        $fill[self::perm($perm)] = true;
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
    $checked_perms = array_values(array_filter($this->all_perms, fn($perm): bool => (bool)($data[self::perm($perm)] ?? false)));
    
    foreach($checked_perms as $perm){
      $dependency = SettingsRolesModel::PERM_DEPENDENCIES[$perm] ?? null;
      
      if ($dependency !== null && !in_array($dependency, $checked_perms, true)){
        $validator->bool(self::perm($perm))
                  ->isTrue(fn($ignore): bool => in_array($dependency, $checked_perms, true), 'This permission requires the \''.SettingsRolesModel::PERM_NAMES[$dependency].'\' permission.');
      }
    }
    
    try{
      $validator->validate();
      $perms = new TrackerPermTable(DB::get(), $this->getTracker());
      
      if ($perms->getRoleTitleIfNotSpecial($this->role_id) === null){
        $this->form->addMessage(FormComponent::MESSAGE_ERROR, Text::warning('Invalid role.'));
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
