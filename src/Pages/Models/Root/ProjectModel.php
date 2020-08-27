<?php
declare(strict_types = 1);

namespace Pages\Models\Root;

use Database\DB;
use Database\Filters\General\Pagination;
use Database\Filters\Types\ProjectFilter;
use Database\Objects\UserProfile;
use Database\SQL;
use Database\Tables\ProjectTable;
use Database\Validation\ProjectFields;
use Exception;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Table\TableComponent;
use Pages\IModel;
use Pages\Models\BasicRootPageModel;
use PDOException;
use Routing\Link;
use Routing\Request;
use Session\Permissions\SystemPermissions;
use Session\Session;
use Validation\FormValidator;
use Validation\ValidationException;

class ProjectModel extends BasicRootPageModel{
  public const ACTION_CREATE = 'Create';
  
  private SystemPermissions $perms;
  private TableComponent $table;
  private ?FormComponent $form;
  
  public function __construct(Request $req, SystemPermissions $perms){
    parent::__construct($req);
    
    $this->perms = $perms;
    
    $this->table = new TableComponent();
    $this->table->ifEmpty('No projects found. Some projects may not be visible to your account.');
    
    $this->table->addColumn('Name')->sort('name')->width(50)->wrap()->bold();
    $this->table->addColumn('Link')->width(50);
    
    if ($perms->check(SystemPermissions::MANAGE_PROJECTS)){
      $this->table->addColumn('Actions')->tight()->right();
    }
    
    if ($perms->check(SystemPermissions::CREATE_PROJECT)){
      $this->form = new FormComponent(self::ACTION_CREATE);
      $this->form->startTitledSection('Create Project');
      $this->form->setMessagePlacementHere();
      
      $this->form->addTextField('Name')->type('text');
      $this->form->addTextField('Url')->type('text');
      $this->form->addCheckBox('Hidden');
      $this->form->addButton('submit', 'Create Project')->icon('pencil');
      
      $this->form->endTitledSection();
    }
    else{
      $this->form = null;
    }
  }
  
  public function load(): IModel{
    parent::load();
    
    if ($this->perms->check(SystemPermissions::LIST_VISIBLE_PROJECTS)){
      $filter = new ProjectFilter();
      
      if (!$this->perms->check(SystemPermissions::LIST_ALL_PROJECTS)){
        $filter = $filter->visibleTo(Session::get()->getLogonUser());
      }
      
      $projects = new ProjectTable(DB::get());
      
      $filtering = $filter->filter();
      $total_count = $projects->countProjects($filter);
      $pagination = $filter->page($total_count);
      $sorting = $filter->sort($this->getReq());
      
      foreach($projects->listProjects($filter) as $project){
        $url_enc = rawurlencode($project->getUrl());
        $link = '<a href="'.Link::fromRoot('project', $url_enc).'" class="plain">'.$project->getUrlSafe().' <span class="icon icon-out"></span></a>';
        
        $row = [$project->getNameSafe(), $link];
        
        if ($this->perms->check(SystemPermissions::MANAGE_PROJECTS)){
          $row[] = '<a href="'.Link::fromRoot('project', $url_enc, 'delete').'" class="icon"><span class="icon icon-circle-cross icon-color-red"></span></a>';
        }
        
        $this->table->addRow($row);
      }
      
      $this->table->setupColumnSorting($sorting);
      $this->table->setPaginationFooter($this->getReq(), $pagination)->elementName('projects');
      
      $header = $this->table->setFilteringHeader($filtering);
      $header->addTextField('name')->label('Name');
      $header->addTextField('url')->label('Link');
    }
    else{
      $this->table->setPaginationFooter($this->getReq(), Pagination::empty())->elementName('projects');
    }
    
    return $this;
  }
  
  public function getProjectTable(): TableComponent{
    return $this->table;
  }
  
  public function getCreateForm(): ?FormComponent{
    return $this->form;
  }
  
  public function createProject(array $data, ?UserProfile $owner): bool{
    if (!$this->form->accept($data) || $owner === null){
      return false;
    }
    
    $validator = new FormValidator($data);
    $name = ProjectFields::name($validator);
    $url = ProjectFields::url($validator);
    $hidden = ProjectFields::hidden($validator);
    
    try{
      $validator->validate();
      $projects = new ProjectTable(DB::get());
      $projects->addProject($name, $url, $hidden, $owner);
      return true;
    }catch(ValidationException $e){
      $this->form->invalidateFields($e->getFields());
    }catch(PDOException $e){
      if ($e->getCode() === SQL::CONSTRAINT_VIOLATION){
        try{
          $projects = new ProjectTable(DB::get());
          
          if ($projects->checkUrlExists($url)){
            $this->form->invalidateField('Url', 'Project with this URL already exists.');
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
