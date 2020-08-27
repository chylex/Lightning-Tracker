<?php
declare(strict_types = 1);

namespace Pages\Views\Project;

use Pages\Components\DateTimeComponent;
use Pages\Components\Forms\FormComponent;
use Pages\Components\ProgressBarComponent;
use Pages\Components\Sidemenu\SidemenuComponent;
use Pages\Components\SplitComponent;
use Pages\Components\Table\TableComponent;
use Pages\Models\Project\IssuesModel;
use Pages\Views\AbstractProjectPage;

class IssuesPage extends AbstractProjectPage{
  private IssuesModel $model;
  
  public function __construct(IssuesModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getHeading(): string{
    return 'Issues';
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_FULL;
  }
  
  protected function echoPageHead(): void{
    SplitComponent::echoHead();
    TableComponent::echoHead();
    FormComponent::echoHead();
    SidemenuComponent::echoHead();
    ProgressBarComponent::echoHead();
    DateTimeComponent::echoHead();
    
    if (DEBUG){
      echo '<link rel="stylesheet" type="text/css" href="~resources/css/issues.css?v='.TRACKER_RESOURCE_VERSION.'">';
    }
  }
  
  protected function echoPageBody(): void{
    $split = new SplitComponent(80);
    $split->collapseAt(1024, true);
    $split->setRightWidthLimits(250, 400);
    
    $split->addLeft($this->model->getIssueTable());
    $split->addRightIfNotNull($this->model->getMenuActions());
    $split->addRightIfNotNull($this->model->getActiveMilestoneComponent());
    
    $split->echoBody();
  }
}

?>