<?php
declare(strict_types = 1);

namespace Pages\Models\Tracker;

use Database\DB;
use Database\Filters\AbstractFilter;
use Database\Filters\Pagination;
use Database\Filters\Types\TrackerMemberFilter;
use Database\Objects\TrackerInfo;
use Database\SQL;
use Database\Tables\TrackerMemberTable;
use Database\Tables\TrackerPermTable;
use Database\Tables\UserTable;
use Exception;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Table\TableComponent;
use Pages\IModel;
use Pages\Models\BasicTrackerPageModel;
use PDOException;
use Routing\Request;
use Session\Permissions;
use Session\Session;

class MembersModel extends BasicTrackerPageModel{
  public const ACTION_INVITE = 'Invite';
  public const ACTION_REMOVE = 'Remove';
  
  public const PERM_LIST = 'members.list';
  public const PERM_MANAGE = 'members.manage';
  
  private const USERS_PER_PAGE = 15;
  
  private Permissions $perms;
  private TableComponent $table;
  private ?FormComponent $form;
  // TODO add a way to edit an existing member
  
  public function __construct(Request $req, TrackerInfo $tracker, Permissions $perms){
    parent::__construct($req, $tracker);
    
    $this->perms = $perms;
    $this->perms->requireTracker($tracker, self::PERM_LIST);
    
    $this->table = new TableComponent();
    $this->table->ifEmpty('No members found.');
    
    $this->table->addColumn('Username')->width(60)->bold();
    $this->table->addColumn('Role')->width(40);
    
    if ($perms->checkTracker($tracker, self::PERM_MANAGE)){
      $this->table->addColumn('Actions')->right()->tight();
      
      $roles = (new TrackerPermTable(DB::get(), $tracker))->listRoles();
      
      $this->form = new FormComponent(self::ACTION_INVITE);
      $this->form->startTitledSection('Invite User');
      
      $this->form->addTextField('Name')
                 ->label('Username')
                 ->type('text')
                 ->autocomplete('username');
      
      $select_role = $this->form->addSelect('Role')
                                ->dropdown()
                                ->addOption('', '(Default)');
      
      foreach($roles as $role){
        $select_role->addOption(strval($role->getId()), $role->getTitleSafe());
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
    
    $logon_user = Session::get()->getLogonUser();
    $logon_user_id = $logon_user === null ? -1 : $logon_user->getId();
    
    $tracker = $this->getTracker();
    $owner_id = $tracker->getOwnerId();
    
    $filter = new TrackerMemberFilter();
    $members = new TrackerMemberTable(DB::get(), $tracker);
    $total_count = $members->countMembers($filter);
    
    $pagination = Pagination::fromGet(AbstractFilter::GET_PAGE, $total_count, self::USERS_PER_PAGE);
    $filter = $filter->page($pagination);
    
    foreach($members->listMembers($filter) as $member){
      $row = [$member->getUserNameSafe(),
              $member->getRoleTitleSafe() ?? '<span class="missing">Default</span>'];
      
      if ($this->perms->checkTracker($tracker, self::PERM_MANAGE)){
        $user_id = $member->getUserId();
        
        if ($user_id === $logon_user_id || $user_id === $owner_id){
          $row[] = '';
        }
        else{
          $form = new FormComponent(self::ACTION_REMOVE);
          $form->requireConfirmation('This action cannot be reversed. Do you want to continue?');
          $form->addHidden('User', strval($user_id));
          $form->addIconButton('submit', 'circle-cross')->color('red');
          $row[] = $form;
        }
      }
      
      $this->table->addRow($row);
    }
    
    $this->table->setPaginationFooter($this->getReq(), $pagination)->elementName('members');
    
    return $this;
  }
  
  public function getMemberTable(): TableComponent{
    return $this->table;
  }
  
  public function getInviteForm(): ?FormComponent{
    return $this->form;
  }
  
  public function inviteUser(array $data): bool{
    $tracker = $this->getTracker();
    $this->perms->requireTracker($tracker, self::PERM_MANAGE);
    
    if (!$this->form->accept($data)){
      return false;
    }
    
    $db = DB::get();
    
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
      
      $members = new TrackerMemberTable($db, $tracker);
      $members->setRole($user_id, $role_id); // TODO add a proper invitation system
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
    $tracker = $this->getTracker();
    $this->perms->requireTracker($tracker, self::PERM_MANAGE);
    
    if (!isset($data['User']) || !is_numeric($data['User'])){
      return false;
    }
    
    $members = new TrackerMemberTable(DB::get(), $tracker);
    $members->removeUserId((int)$data['User']);
    return true;
  }
}

?>
