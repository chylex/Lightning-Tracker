<?php
declare(strict_types = 1);

namespace Pages\Models\Root;

use Database\DB;
use Database\Filters\General\Pagination;
use Database\Filters\Types\TrackerFilter;
use Database\Objects\UserProfile;
use Database\SQL;
use Database\Tables\TrackerTable;
use Database\Validation\TrackerFields;
use Exception;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Table\TableComponent;
use Pages\IModel;
use Pages\Models\BasicRootPageModel;
use PDOException;
use Routing\Link;
use Routing\Request;
use Session\Permissions;
use Session\Session;
use Validation\FormValidator;
use Validation\ValidationException;

class TrackersModel extends BasicRootPageModel{
  public const ACTION_CREATE = 'Create';
  
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
    
    $this->table->addColumn('Name')->sort('name')->width(50)->wrap()->bold();
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
      
      $filtering = $filter->filter();
      $total_count = $trackers->countTrackers($filter);
      $pagination = $filter->page($total_count);
      $sorting = $filter->sort($this->getReq());
      
      foreach($trackers->listTrackers($filter) as $tracker){
        $url_enc = rawurlencode($tracker->getUrl());
        $link = '<a href="'.Link::fromRoot('tracker', $url_enc).'" class="plain">'.$tracker->getUrlSafe().' <span class="icon icon-out"></span></a>';
        
        $row = [$tracker->getNameSafe(), $link];
        
        if ($this->perms->checkSystem(self::PERM_EDIT)){
          $row[] = '<a href="'.Link::fromRoot('tracker', $url_enc, 'delete').'" class="icon"><span class="icon icon-circle-cross icon-color-red"></span></a>';
        }
        
        $this->table->addRow($row);
      }
      
      $this->table->setupColumnSorting($sorting);
      $this->table->setPaginationFooter($this->getReq(), $pagination)->elementName('trackers');
      
      $header = $this->table->setFilteringHeader($filtering);
      $header->addTextField('name')->label('Name');
      $header->addTextField('url')->label('Link');
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
    if (!$this->form->accept($data) || $owner === null){
      return false;
    }
    
    $validator = new FormValidator($data);
    $name = TrackerFields::name($validator);
    $url = TrackerFields::url($validator);
    $hidden = TrackerFields::hidden($validator);
    
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
}

?>
