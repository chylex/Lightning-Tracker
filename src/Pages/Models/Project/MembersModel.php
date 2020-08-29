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
use Pages\IModel;
use Pages\Models\BasicProjectPageModel;
use Routing\Link;
use Routing\Request;
use Session\Permissions\ProjectPermissions;
use Session\Session;

class MembersModel extends BasicProjectPageModel{
  public const ACTION_INVITE = 'Invite';
  
  private ProjectPermissions $perms;
  private TableComponent $table;
  private ?FormComponent $form;
  
  /**
   * @var string[]
   */
  private array $editable_roles = [];
  
  public function __construct(Request $req, ProjectInfo $project, ProjectPermissions $perms){
    parent::__construct($req, $project);
    
    $this->perms = $perms;
    
    $this->table = new TableComponent();
    $this->table->ifEmpty('No members found.');
    
    $this->table->addColumn('Username')->sort('name')->width(60)->wrap()->bold();
    $this->table->addColumn('Role')->sort('role_order')->width(40);
    
    if ($perms->check(ProjectPermissions::MANAGE_MEMBERS)){
      $this->table->addColumn('Actions')->right()->tight();
      
      $this->form = new FormComponent(self::ACTION_INVITE);
      $this->form->startTitledSection('Invite User');
      $this->form->setMessagePlacementHere();
      
      $this->form->addTextField('Name')
                 ->label('Username')
                 ->type('text')
                 ->autocomplete('username');
      
      $select_role = $this->form->addSelect('Role')
                                ->dropdown()
                                ->addOption('', '(Default)');
      
      $logon_user_id = Session::get()->getLogonUserId();
      
      if ($logon_user_id !== null){
        $this->editable_roles[] = '';
        
        foreach((new ProjectPermTable(DB::get(), $project))->listRolesAssignableBy($logon_user_id) as $role){
          $role_id_str = (string)$role->getId();
          $select_role->addOption($role_id_str, $role->getTitle());
          $this->editable_roles[] = $role_id_str;
        }
      }
      
      $this->form->addButton('submit', 'Invite User')
                 ->icon('user');
      
      $this->form->endTitledSection();
    }
    else{
      $this->form = null;
    }
  }
  
  public function load(): IModel{
    parent::load();
    
    $req = $this->getReq();
    
    $project = $this->getProject();
    $owner_id = $project->getOwnerId();
    $logon_user_id = Session::get()->getLogonUserId();
    
    $filter = new ProjectMemberFilter();
    $members = new ProjectMemberTable(DB::get(), $project);
    
    $filtering = $filter->filter();
    $total_count = $members->countMembers($filter);
    $pagination = $filter->page($total_count);
    $sorting = $filter->sort($this->getReq());
    
    foreach($members->listMembers($filter) as $member){
      $row = [$member->getUserNameSafe(),
              $member->getRoleTitleSafe() ?? Text::missing('Default')];
      
      $user_id = $member->getUserId();
      $user_id_str = $user_id->formatted();
      
      $can_edit = !$user_id->equals($logon_user_id) && !$user_id->equals($owner_id) && in_array((string)($member->getRoleId() ?? ''), $this->editable_roles, true);
      
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
      
      $row = $this->table->addRow($row);
      
      if ($can_edit && $this->perms->check(ProjectPermissions::MANAGE_MEMBERS)){
        $row->link(Link::fromBase($this->getReq(), 'members', $user_id_str));
      }
    }
    
    $this->table->setupColumnSorting($sorting);
    $this->table->setPaginationFooter($this->getReq(), $pagination)->elementName('members');
    
    $header = $this->table->setFilteringHeader($filtering);
    $header->addTextField('name')->label('Username');
    
    $filtering_role = $header->addMultiSelect('role')->label('Role');
    $filtering_role->addOption('', Text::missing('Default'));
    
    foreach((new ProjectPermTable(DB::get(), $project))->listRoles() as $role){
      $title = $role->getTitle();
      $filtering_role->addOption($title, Text::plain($title));
    }
    
    return $this;
  }
  
  public function getMemberTable(): TableComponent{
    return $this->table;
  }
  
  public function getInviteForm(): ?FormComponent{
    return $this->form;
  }
  
  public function inviteUser(array $data): bool{
    if (!$this->form->accept($data)){
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
      $this->form->invalidateField('Role', 'Invalid role.');
      return false;
    }
    
    $user_id = null;
    
    try{
      $users = new UserTable($db);
      $user_id = $users->findIdByName($name);
      
      if ($user_id === null){
        $this->form->invalidateField('Name', 'User not found.');
        return false;
      }
      elseif ($user_id->equals($project->getOwnerId())){
        $this->form->invalidateField('Name', 'User is the owner of this project.');
        return false;
      }
      
      if ($role_id !== null && !(new ProjectPermTable($db, $project))->isRoleAssignableBy($role_id, Session::get()->getLogonUserIdOrThrow())){
        $this->form->invalidateField('Role', 'Invalid role.');
        return false;
      }
      
      $members = new ProjectMemberTable($db, $project);
      
      if ($members->checkMembershipExists($user_id)){
        $this->form->invalidateField('Name', 'User is already a member of this project.');
        return false;
      }
      
      $members->addMember($user_id, $role_id); // TODO add a proper invitation system
      return true;
    }catch(Exception $e){
      $this->form->onGeneralError($e);
    }
    
    return false;
  }
}

?>
