<?php
declare(strict_types = 1);

namespace Pages\Models\Project;

use Database\DB;
use Database\Filters\Types\ProjectMemberFilter;
use Database\Objects\ProjectInfo;
use Database\Tables\ProjectMemberTable;
use Database\Tables\ProjectPermTable;
use Database\Tables\UserTable;
use Exception;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Forms\IconButtonFormComponent;
use Pages\Components\Table\TableComponent;
use Pages\Components\Text;
use Pages\Models\BasicProjectPageModel;
use Routing\Link;
use Routing\Request;
use Session\Permissions\ProjectPermissions;
use Session\Session;

class MembersModel extends BasicProjectPageModel{
  public const ACTION_INVITE = 'Invite';
  
  private ProjectPermissions $perms;
  
  private FormComponent $invite_form;
  
  /**
   * @var string[]
   */
  private array $editable_roles = [];
  
  public function __construct(Request $req, ProjectInfo $project, ProjectPermissions $perms){
    parent::__construct($req, $project);
    $this->perms = $perms;
    
    if ($perms->check(ProjectPermissions::MANAGE_MEMBERS)){
      $logon_user_id = Session::get()->getLogonUserId();
      
      if ($logon_user_id !== null){
        foreach((new ProjectPermTable(DB::get(), $project))->listRolesAssignableBy($logon_user_id) as $role){
          $this->editable_roles[$role->getId()] = $role->getTitle();
        }
      }
    }
  }
  
  public function createMemberTable(): TableComponent{
    $req = $this->getReq();
    $project = $this->getProject();
    $logon_user_id = Session::get()->getLogonUserId();
    
    $table = new TableComponent();
    $table->ifEmpty('No members found.');
    
    $table->addColumn('Username')->sort('name')->width(60)->wrap()->bold();
    $table->addColumn('Role')->sort('role_order')->width(40);
    
    if ($this->perms->check(ProjectPermissions::MANAGE_MEMBERS)){
      $table->addColumn('Actions')->right()->tight();
    }
    
    $owner_id = $project->getOwnerId();
    
    $filter = new ProjectMemberFilter();
    $members = new ProjectMemberTable(DB::get(), $project);
    
    $filtering = $filter->filter();
    $total_count = $members->countMembers($filter);
    $pagination = $filter->page($total_count);
    $sorting = $filter->sort($this->getReq());
    
    foreach($members->listMembers($filter) as $member){
      /** @noinspection ProperNullCoalescingOperatorUsageInspection */
      $row = [$member->getUserNameSafe(),
              $member->getRoleTitleSafe() ?? Text::missing('Default')];
      
      $user_id = $member->getUserId();
      $user_id_str = $user_id->formatted();
      
      $can_edit = (
          $this->perms->check(ProjectPermissions::MANAGE_MEMBERS) &&
          !$user_id->equals($logon_user_id) &&
          !$user_id->equals($owner_id) &&
          ($member->getRoleId() === null || array_key_exists($member->getRoleId(), $this->editable_roles))
      );
      
      if ($this->perms->check(ProjectPermissions::MANAGE_MEMBERS)){
        if ($can_edit){
          $link_delete = Link::fromBase($req, 'members', $user_id_str, 'remove');
          $btn_delete = new IconButtonFormComponent($link_delete, 'circle-cross');
          $btn_delete->color('red');
          $row[] = $btn_delete;
        }
        else{
          $row[] = '';
        }
      }
      
      $row = $table->addRow($row);
      
      if ($can_edit){
        $row->link(Link::fromBase($this->getReq(), 'members', $user_id_str));
      }
    }
    
    $table->setupColumnSorting($sorting);
    $table->setPaginationFooter($this->getReq(), $pagination)->elementName('members');
    
    $header = $table->setFilteringHeader($filtering);
    $header->addTextField('name')->label('Username');
    
    $filtering_role = $header->addMultiSelect('role')->label('Role');
    $filtering_role->addOption('', Text::missing('Default'));
    
    foreach((new ProjectPermTable(DB::get(), $project))->listRoles() as $role){
      $title = $role->getTitle();
      $filtering_role->addOption($title, Text::plain($title));
    }
    
    return $table;
  }
  
  public function getInviteForm(): ?FormComponent{
    if (!$this->perms->check(ProjectPermissions::MANAGE_MEMBERS)){
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
      
      if ($role_id !== null && !(new ProjectPermTable($db, $project))->isRoleAssignableBy($role_id, Session::get()->getLogonUserIdOrThrow())){
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
