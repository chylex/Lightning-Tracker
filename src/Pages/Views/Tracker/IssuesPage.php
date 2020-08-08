<?php
declare(strict_types = 1);

namespace Pages\Views\Tracker;

use Pages\Components\Forms\FormComponent;
use Pages\Components\ProgressBarComponent;
use Pages\Components\Sidemenu\SidemenuComponent;
use Pages\Components\SplitComponent;
use Pages\Components\Table\TableComponent;
use Pages\Models\Tracker\IssuesModel;
use Pages\Views\AbstractTrackerPage;

class IssuesPage extends AbstractTrackerPage{
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
    
    echo <<<HTML
<link rel="stylesheet" type="text/css" href="~resources/css/issues.css">
HTML;
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
