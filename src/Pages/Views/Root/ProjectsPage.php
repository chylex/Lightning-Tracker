<?php
declare(strict_types = 1);

namespace Pages\Views\Root;

use Pages\Components\Forms\FormComponent;
use Pages\Components\SplitComponent;
use Pages\Components\Table\TableComponent;
use Pages\Components\TitledSectionComponent;
use Pages\Models\Root\ProjectModel;
use Pages\Views\AbstractPage;

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
    
    $split->addLeft($this->model->createProjectTable());
    $split->addRightIfNotNull(TitledSectionComponent::wrap('Create Project', $this->model->getCreateForm()));
    
    $split->echoBody();
  }
}

?>
