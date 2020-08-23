<?php
declare(strict_types = 1);

namespace Pages\Models\Tracker;

use Database\DB;
use Database\Filters\Types\TrackerMemberFilter;
use Database\Objects\TrackerInfo;
use Database\SQL;
use Database\Tables\TrackerMemberTable;
use Database\Tables\TrackerPermTable;
use Database\Tables\UserTable;
use Exception;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Table\TableComponent;
use Pages\Components\Text;
use Pages\IModel;
use Pages\Models\BasicTrackerPageModel;
use PDOException;
use Routing\Link;
use Routing\Request;
use Session\Permissions;
use Session\Session;

class MembersModel extends BasicTrackerPageModel{
  public const ACTION_INVITE = 'Invite';
  public const ACTION_REMOVE = 'Remove';
  
  public const PERM_LIST = 'members.list';
  public const PERM_MANAGE = 'members.manage';
  
  private Permissions $perms;
  private TableComponent $table;
  private ?FormComponent $form;
  
  /**
   * @var string[]
   */
  private array $editable_roles = [];
  
  public function __construct(Request $req, TrackerInfo $tracker, Permissions $perms){
    parent::__construct($req, $tracker);
    
    $this->perms = $perms;
    $this->perms->requireTracker($tracker, self::PERM_LIST);
    
    $this->table = new TableComponent();
    $this->table->ifEmpty('No members found.');
    
    $this->table->addColumn('Username')->sort('name')->width(60)->wrap()->bold();
    $this->table->addColumn('Role')->sort('role_order')->width(40);
    
    if ($perms->checkTracker($tracker, self::PERM_MANAGE)){
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
        
        foreach((new TrackerPermTable(DB::get(), $tracker))->listRolesAssignableBy($logon_user_id) as $role){
          $role_id_str = strval($role->getId());
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
    
    $tracker = $this->getTracker();
    $owner_id = $tracker->getOwnerId();
    $logon_user_id = Session::get()->getLogonUserId();
    
    $filter = new TrackerMemberFilter();
    $members = new TrackerMemberTable(DB::get(), $tracker);
    
    $filtering = $filter->filter();
    $total_count = $members->countMembers($filter);
    $pagination = $filter->page($total_count);
    $sorting = $filter->sort($this->getReq());
    
    foreach($members->listMembers($filter) as $member){
      $name_safe = $member->getUserNameSafe();
      
      $row = [$name_safe,
              $member->getRoleTitleSafe() ?? Text::missing('Default')];
      
      $user_id = $member->getUserId();
      $can_edit = $user_id !== $logon_user_id && $user_id !== $owner_id && in_array(strval($member->getRoleId() ?? ''), $this->editable_roles, true);
      
      if ($this->perms->checkTracker($tracker, self::PERM_MANAGE)){
        if ($can_edit){
          $form = new FormComponent(self::ACTION_REMOVE);
          $form->requireConfirmation('This action cannot be reversed. Do you want to continue?');
          $form->addHidden('User', strval($user_id));
          $form->addIconButton('submit', 'circle-cross')->color('red');
          $row[] = $form;
        }
        else{
          $row[] = '';
        }
      }
      
      $row = $this->table->addRow($row);
      
      if ($this->perms->checkTracker($tracker, self::PERM_MANAGE) && $can_edit){
        $row->link(Link::fromBase($this->getReq(), 'members', $name_safe));
      }
    }
    
    $this->table->setupColumnSorting($sorting);
    $this->table->setPaginationFooter($this->getReq(), $pagination)->elementName('members');
    
    $header = $this->table->setFilteringHeader($filtering);
    $header->addTextField('name')->label('Username');
    
    $filtering_role = $header->addMultiSelect('role')->label('Role');
    $filtering_role->addOption('', Text::missing('Default'));
    
    foreach((new TrackerPermTable(DB::get(), $tracker))->listRoles() as $role){
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
    $tracker = $this->getTracker();
    
    $name = $data['Name'];
    $role = $data['Role'];
    $user_id = null;
    
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
    
    try{
      $users = new UserTable($db);
      $user_id = $users->findIdByName($name);
      
      if ($user_id === null){
        $this->form->invalidateField('Name', 'User not found.');
        return false;
      }
      elseif ($user_id === $tracker->getOwnerId()){
        $this->form->invalidateField('Name', 'User is the owner of this tracker.');
        return false;
      }
      
      if ($role_id !== null && !(new TrackerPermTable($db, $tracker))->isRoleAssignableBy($role_id, Session::get()->getLogonUserIdOrThrow())){
        $this->form->invalidateField('Role', 'Invalid role.');
        return false;
      }
      
      $members = new TrackerMemberTable($db, $tracker);
      $members->addMember($user_id, $role_id); // TODO add a proper invitation system
      return true;
    }catch(PDOException $e){
      if ($e->getCode() === SQL::CONSTRAINT_VIOLATION){
        try{
          $members = new TrackerMemberTable($db, $tracker);
          
          if ($user_id !== null && $members->checkMembershipExists($user_id)){
            $this->form->invalidateField('Name', 'User is already a member of this tracker.');
            return false;
          }
        }catch(Exception $e){
          $this->form->onGeneralError($e);
          return false;
        }
      }
      
      $this->form->onGeneralError($e);
    }catch(Exception $e){
      $this->form->onGeneralError($e);
    }
    
    return false;
  }
  
  public function removeMember(array $data): bool{ // TODO make it a dedicated page with additional checks
    $user = get_int($data, 'User');
    
    if ($user === null){
      return false;
    }
    
    $db = DB::get();
    $tracker = $this->getTracker();
    
    $members = new TrackerMemberTable($db, $tracker);
    $role = $members->getRoleIdStr($user);
    
    if (!MemberEditModel::canEditMember(Session::get()->getLogonUserIdOrThrow(), $user, empty($role) ? null : intval($role), $tracker)){
      return false;
    }
    
    $members->removeUserId($user);
    return true;
  }
}

?>
