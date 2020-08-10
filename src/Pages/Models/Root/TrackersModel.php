<?php
declare(strict_types = 1);

namespace Pages\Models\Root;

use Database\DB;
use Database\Filters\Pagination;
use Database\Filters\Types\TrackerFilter;
use Database\Objects\UserProfile;
use Database\SQL;
use Database\Tables\TrackerTable;
use Exception;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Table\TableComponent;
use Pages\IModel;
use Pages\Models\BasicRootPageModel;
use PDOException;
use Routing\Request;
use Session\Permissions;
use Session\Session;
use Validation\ValidationException;
use Validation\Validator;

class TrackersModel extends BasicRootPageModel{
  public const ACTION_CREATE = 'Create';
  public const ACTION_DELETE = 'Delete';
  
  public const PERM_LIST = 'trackers.list';
  public const PERM_LIST_HIDDEN = 'trackers.list.hidden';
  public const PERM_ADD = 'trackers.add';
  public const PERM_EDIT = 'trackers.edit';
  
  private Permissions $perms;
  private TableComponent $table;
  private ?FormComponent $form;
  
  public function __construct(Request $req, Permissions $perms){
    parent::__construct($req);
    
    $this->perms = $perms;
    
    $this->table = new TableComponent();
    $this->table->ifEmpty('No trackers found. Some trackers may not be visible to your account.');
    
    $this->table->addColumn('Name')->width(50)->bold();
    $this->table->addColumn('Link')->width(50);
    
    if ($perms->checkSystem(self::PERM_EDIT)){
      $this->table->addColumn('Actions')->tight()->right();
    }
    
    if ($perms->checkSystem(self::PERM_ADD)){
      $this->form = new FormComponent(self::ACTION_CREATE);
      $this->form->startTitledSection('Create Tracker');
      $this->form->setMessagePlacementHere();
      
      $this->form->addTextField('Name')->type('text');
      $this->form->addTextField('Url')->type('text');
      $this->form->addCheckBox('Hidden');
      $this->form->addButton('submit', 'Create Tracker')->icon('pencil');
      
      $this->form->endTitledSection();
    }
    else{
      $this->form = null;
    }
  }
  
  public function load(): IModel{
    parent::load();
    
    if ($this->perms->checkSystem(self::PERM_LIST)){
      $filter = new TrackerFilter();
      
      if (!$this->perms->checkSystem(self::PERM_LIST_HIDDEN)){
        $filter = $filter->visibleTo(Session::get()->getLogonUser());
      }
      
      $trackers = new TrackerTable(DB::get());
      $total_count = $trackers->countTrackers($filter);
      
      $pagination = Pagination::fromGlobals($total_count);
      $filter = $filter->page($pagination);
      
      foreach($trackers->listTrackers($filter) as $tracker){
        $tracker_id = $tracker->getId();
        
        $url = BASE_URL_ENC.'/tracker/'.rawurlencode($tracker->getUrl());
        $link = '<a href="'.$url.'" class="plain">'.$tracker->getUrlSafe().' <span class="icon icon-out"></span></a>';
        
        $row = [$tracker->getNameSafe(), $link];
        
        if ($this->perms->checkSystem(self::PERM_EDIT)){
          $form = new FormComponent(self::ACTION_DELETE);
          $form->requireConfirmation('This action cannot be reversed. Do you want to continue?');
          $form->addHidden('Tracker', strval($tracker_id));
          $form->addIconButton('submit', 'circle-cross')->color('red');
          $row[] = $form;
        }
        
        $this->table->addRow($row);
      }
      
      $this->table->setPaginationFooter($this->getReq(), $pagination)->elementName('trackers');
    }
    else{
      $this->table->setPaginationFooter($this->getReq(), Pagination::empty())->elementName('trackers');
    }
    
    return $this;
  }
  
  public function getTrackerTable(): TableComponent{
    return $this->table;
  }
  
  public function getCreateForm(): ?FormComponent{
    return $this->form;
  }
  
  public function createTracker(array $data, ?UserProfile $owner): bool{
    $this->perms->requireSystem(self::PERM_ADD);
    
    if (!$this->form->accept($data) || $owner === null){
      return false;
    }
    
    $name = $data['Name'];
    $url = $data['Url'];
    $hidden = (bool)($data['Hidden'] ?? false);
    
    $validator = new Validator();
    $validator->str('Name', $name)->notEmpty();
    $validator->str('Url', $url)->notEmpty()->notContains('/')->notContains('\\');
    
    try{
      $validator->validate();
      $trackers = new TrackerTable(DB::get());
      $trackers->addTracker($name, $url, $hidden, $owner);
      return true;
    }catch(ValidationException $e){
      $this->form->invalidateFields($e->getFields());
    }catch(PDOException $e){
      if ($e->getCode() === SQL::CONSTRAINT_VIOLATION){
        try{
          $trackers = new TrackerTable(DB::get());
          
          if ($trackers->checkUrlExists($url)){
            $this->form->invalidateField('Url', 'Tracker with this URL already exists.');
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
  
  public function deleteTracker(array $data): bool{ // TODO make it a dedicated page with additional checks
    $this->perms->requireSystem(self::PERM_EDIT);
    
    if (!isset($data['Tracker']) || !is_numeric($data['Tracker'])){
      return false;
    }
    
    $trackers = new TrackerTable(DB::get());
    $trackers->deleteById((int)$data['Tracker']);
    return true;
  }
}

?>
