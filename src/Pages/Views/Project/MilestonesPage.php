<?php
declare(strict_types = 1);

namespace Pages\Views\Project;

use Pages\Components\DateTimeComponent;
use Pages\Components\Forms\FormComponent;
use Pages\Components\ProgressBarComponent;
use Pages\Components\SplitComponent;
use Pages\Components\Table\TableComponent;
use Pages\Models\Project\MilestonesModel;
use Pages\Views\AbstractProjectPage;

class MilestonesPage extends AbstractProjectPage{
  private MilestonesModel $model;
  
  public function __construct(MilestonesModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getHeading(): string{
    return 'Milestones';
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_FULL;
  }
  
  protected function echoPageHead(): void{
    SplitComponent::echoHead();
    TableComponent::echoHead();
    FormComponent::echoHead();
    ProgressBarComponent::echoHead();
    DateTimeComponent::echoHead();
  }
  
  protected function echoPageBody(): void{
    $split = new SplitComponent(75);
    $split->collapseAt(1024, true);
    $split->setRightWidthLimits(250, 500);
    
    $split->addLeft($this->model->getMilestoneTable());
    $split->addRightIfNotNull($this->model->getCreateForm());
    
    $split->echoBody();
  }
}

?>
