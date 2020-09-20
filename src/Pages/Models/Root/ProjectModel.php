<?php
declare(strict_types = 1);

namespace Pages\Models\Root;

use Database\DB;
use Database\Filters\General\Pagination;
use Database\Filters\Types\ProjectFilter;
use Database\Objects\ProjectInfo;
use Database\Objects\UserProfile;
use Database\Tables\ProjectTable;
use Database\Validation\ProjectFields;
use Exception;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Table\TableComponent;
use Pages\Models\BasicRootPageModel;
use Routing\Request;
use Session\Permissions\SystemPermissions;
use Session\Session;
use Validation\FormValidator;
use Validation\ValidationException;

class ProjectModel extends BasicRootPageModel{
  public const ACTION_CREATE = 'Create';
  
  private SystemPermissions $perms;
  
  private FormComponent $create_form;
  
  public function __construct(Request $req, SystemPermissions $perms){
    parent::__construct($req);
    $this->perms = $perms;
  }
  
  public function canViewPubliclyVisibleProjects(): bool{
    return $this->perms->check(SystemPermissions::LIST_VISIBLE_PROJECTS);
  }
  
  public function canManageProjects(): bool{
    return $this->perms->check(SystemPermissions::MANAGE_PROJECTS);
  }
  
  public function setupProjectTableFilter(TableComponent $table): ProjectFilter{
    $req = $this->getReq();
    
    $filter = new ProjectFilter();
    
    if (!$this->canViewPubliclyVisibleProjects()){
      $table->setPaginationFooter($req, Pagination::empty())->elementName('projects');
      return $filter;
    }
    
    if (!$this->perms->check(SystemPermissions::LIST_ALL_PROJECTS)){
      $filter = $filter->visibleTo(Session::get()->getLogonUser());
    }
    
    $projects = new ProjectTable(DB::get());
    
    $filtering = $filter->filter();
    $total_count = $projects->countProjects($filter);
    $pagination = $filter->page($total_count);
    $sorting = $filter->sort($req);
    
    $table->setupColumnSorting($sorting);
    $table->setPaginationFooter($req, $pagination)->elementName('projects');
    
    $header = $table->setFilteringHeader($filtering);
    $header->addTextField('name')->label('Name');
    $header->addTextField('url')->label('Link');
    
    return $filter;
  }
  
  /**
   * @param ProjectFilter $filter
   * @return ProjectInfo[]
   */
  public function getProjectList(ProjectFilter $filter): array{
    if ($this->canViewPubliclyVisibleProjects()){
      return (new ProjectTable(DB::get()))->listProjects($filter);
    }
    else{
      return [];
    }
  }
  
  public function getCreateForm(): ?FormComponent{
    if (!$this->perms->check(SystemPermissions::CREATE_PROJECT)){
      return null;
    }
    
    if (isset($this->create_form)){
      return $this->create_form;
    }
    
    $form = new FormComponent(self::ACTION_CREATE);
    $form->addTextField('Name')->type('text');
    $form->addTextField('Url')->type('text');
    $form->addCheckBox('Hidden');
    $form->addButton('submit', 'Create Project')->icon('pencil');
    
    return $this->create_form = $form;
  }
  
  public function createProject(array $data, UserProfile $owner): ?string{
    $form = $this->getCreateForm();
    
    if ($form === null || !$form->accept($data)){
      return null;
    }
    
    $validator = new FormValidator($data);
    $name = ProjectFields::name($validator);
    $url = ProjectFields::url($validator);
    $hidden = ProjectFields::hidden($validator);
    
    try{
      $validator->validate();
      $projects = new ProjectTable(DB::get());
      
      if ($projects->checkUrlExists($url)){
        $form->invalidateField('Url', 'Project with this URL already exists.');
        return null;
      }
      
      $projects->addProject($name, $url, $hidden, $owner);
      return $url;
    }catch(ValidationException $e){
      $form->invalidateFields($e->getFields());
    }catch(Exception $e){
      $form->onGeneralError($e);
    }
    
    return null;
  }
}

?>
