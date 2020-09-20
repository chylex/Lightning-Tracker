<?php
declare(strict_types = 1);

namespace Pages\Views\Root;

use Pages\Components\Forms\FormComponent;
use Pages\Components\SplitComponent;
use Pages\Components\Table\TableComponent;
use Pages\Components\TitledSectionComponent;
use Pages\Models\Root\ProjectModel;
use Pages\Views\AbstractPage;
use Routing\Link;

class ProjectsPage extends AbstractPage{
  private ProjectModel $model;
  
  public function __construct(ProjectModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getTitle(): string{
    return 'Lightning Tracker';
  }
  
  protected function getHeading(): string{
    return 'Projects';
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_FULL;
  }
  
  protected function echoPageHead(): void{
    SplitComponent::echoHead();
    TableComponent::echoHead();
    FormComponent::echoHead();
  }
  
  protected function echoPageBody(): void{
    $split = new SplitComponent(75);
    $split->collapseAt(800, true);
    $split->setRightWidthLimits(250, 500);
    
    $split->addLeft($this->createProjectTable());
    $split->addRightIfNotNull(TitledSectionComponent::wrap('Create Project', $this->model->getCreateForm()));
    
    $split->echoBody();
  }
  
  private function createProjectTable(): TableComponent{
    $can_manage_projects = $this->model->canManageProjects();
    
    $table = new TableComponent();
    $table->ifEmpty('No projects found. Some projects may not be visible to your account.');
    
    $table->addColumn('Name')->sort('name')->width(50)->wrap()->bold();
    $table->addColumn('Link')->width(50);
    
    if ($can_manage_projects){
      $table->addColumn('Actions')->tight()->right();
    }
    
    $filter = $this->model->setupProjectTableFilter($table);
    
    foreach($this->model->getProjectList($filter) as $project){
      $url_enc = rawurlencode($project->getUrl());
      $link = '<a href="'.Link::fromRoot('project', $url_enc).'" class="plain">'.$project->getUrlSafe().' <span class="icon icon-out"></span></a>';
      
      $row = [$project->getNameSafe(), $link];
      
      if ($can_manage_projects){
        $row[] = '<a href="'.Link::fromRoot('project', $url_enc, 'delete').'" class="icon"><span class="icon icon-circle-cross icon-color-red"></span></a>';
      }
      
      $table->addRow($row);
    }
    
    return $table;
  }
}

?>
