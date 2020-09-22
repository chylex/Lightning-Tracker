<?php
declare(strict_types = 1);

namespace Pages\Models\Project;

use Data\UserId;
use Database\DB;
use Database\Filters\Types\ProjectMemberFilter;
use Database\Objects\ProjectInfo;
use Database\Objects\ProjectMember;
use Database\Tables\ProjectMemberTable;
use Database\Tables\ProjectRoleTable;
use Database\Tables\UserTable;
use Exception;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Table\TableComponent;
use Pages\Components\Text;
use Pages\Models\BasicProjectPageModel;
use Routing\Request;
use Session\Permissions\ProjectPermissions;

class MembersModel extends BasicProjectPageModel{
  public const ACTION_INVITE = 'Invite';
  
  private ProjectPermissions $perms;
  private ?UserId $logon_user_id;
  
  private FormComponent $invite_form;
  
  /**
   * @var string[]
   */
  private array $editable_roles = [];
  
  public function __construct(Request $req, ProjectInfo $project, ProjectPermissions $perms, ?UserId $logon_user_id){
    parent::__construct($req, $project);
    $this->perms = $perms;
    $this->logon_user_id = $logon_user_id;
    
    if ($perms->check(ProjectPermissions::MANAGE_MEMBERS) && $logon_user_id !== null){
      foreach((new ProjectRoleTable(DB::get(), $project))->listRolesAssignableBy($logon_user_id) as $role){
        $this->editable_roles[$role->getId()] = $role->getTitle();
      }
    }
  }
  
  public function canManageMembers(): bool{
    return $this->perms->check(ProjectPermissions::MANAGE_MEMBERS);
  }
  
  public function canEditMember(ProjectMember $member): bool{
    $user_id = $member->getUserId();
    
    return (
        $this->perms->check(ProjectPermissions::MANAGE_MEMBERS) &&
        !$user_id->equals($this->logon_user_id) &&
        !$user_id->equals($this->getProject()->getOwnerId()) &&
        ($member->getRoleId() === null || array_key_exists($member->getRoleId(), $this->editable_roles))
    );
  }
  
  public function setupProjectMemberFilter(TableComponent $table): ProjectMemberFilter{
    $filter = new ProjectMemberFilter();
    $members = new ProjectMemberTable(DB::get(), $this->getProject());
    
    $filtering = $filter->filter();
    $total_count = $members->countMembers($filter);
    $pagination = $filter->page($total_count);
    $sorting = $filter->sort($this->getReq());
    
    $table->setupColumnSorting($sorting);
    $table->setPaginationFooter($this->getReq(), $pagination)->elementName('members');
    
    $header = $table->setFilteringHeader($filtering);
    $header->addTextField('name')->label('Username');
    
    $filtering_role = $header->addMultiSelect('role')->label('Role');
    $filtering_role->addOption('', Text::missing('Default'));
    
    foreach((new ProjectRoleTable(DB::get(), $this->getProject()))->listRoles() as $role){
      $title = $role->getTitle();
      $filtering_role->addOption($title, Text::plain($title));
    }
    
    return $filter;
  }
  
  /**
   * @param ProjectMemberFilter $filter
   * @return ProjectMember[]
   */
  public function getMemberList(ProjectMemberFilter $filter): array{
    return (new ProjectMemberTable(DB::get(), $this->getProject()))->listMembers($filter);
  }
  
  public function getInviteForm(): ?FormComponent{
    if ($this->logon_user_id === null || !$this->perms->check(ProjectPermissions::MANAGE_MEMBERS)){
      return null;
    }
    
    if (isset($this->invite_form)){
      return $this->invite_form;
    }
    
    $form = new FormComponent(self::ACTION_INVITE);
    
    $form->addTextField('Name')
         ->label('Username')
         ->type('text')
         ->autocomplete('username');
    
    $select_role = $form->addSelect('Role')
                        ->dropdown()
                        ->addOption('', '(Default)');
    
    foreach($this->editable_roles as $id => $title){
      $select_role->addOption((string)$id, $title);
    }
    
    $form->addButton('submit', 'Invite User')
         ->icon('user');
    
    return $this->invite_form = $form;
  }
  
  public function inviteUser(array $data): bool{
    $form = $this->getInviteForm();
    
    if ($form === null || !$form->accept($data)){
      return false;
    }
    
    $db = DB::get();
    $project = $this->getProject();
    
    $name = $data['Name'];
    $role = $data['Role'];
    
    if (empty($role)){
      $role_id = null;
    }
    elseif (is_numeric($role)){
      $role_id = (int)$role;
    }
    else{
      $form->invalidateField('Role', 'Invalid role.');
      return false;
    }
    
    $user_id = null;
    
    try{
      $users = new UserTable($db);
      $user_id = $users->findIdByName($name);
      
      if ($user_id === null){
        $form->invalidateField('Name', 'User not found.');
        return false;
      }
      elseif ($user_id->equals($project->getOwnerId())){
        $form->invalidateField('Name', 'User is the owner of this project.');
        return false;
      }
      
      if ($role_id !== null && !(new ProjectRoleTable($db, $project))->isRoleAssignableBy($role_id, $this->logon_user_id)){
        $form->invalidateField('Role', 'Invalid role.');
        return false;
      }
      
      $members = new ProjectMemberTable($db, $project);
      
      if ($members->checkMembershipExists($user_id)){
        $form->invalidateField('Name', 'User is already a member of this project.');
        return false;
      }
      
      $members->addMember($user_id, $role_id); // TODO add a proper invitation system
      return true;
    }catch(Exception $e){
      $form->onGeneralError($e);
    }
    
    return false;
  }
}

?>
