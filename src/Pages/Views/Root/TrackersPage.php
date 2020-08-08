<?php
declare(strict_types = 1);

namespace Pages\Views\Root;

use Pages\Components\Forms\FormComponent;
use Pages\Components\SplitComponent;
use Pages\Components\Table\TableComponent;
use Pages\Models\Root\TrackersModel;
use Pages\Views\AbstractPage;

class TrackersPage extends AbstractPage{
  private TrackersModel $model;
  
  public function __construct(TrackersModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getTitle(): string{
    return 'Lightning Tracker';
  }
  
  protected function getHeading(): string{
    return 'Trackers';
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
    
    $split->addLeft($this->model->getTrackerTable());
    $split->addRightIfNotNull($this->model->getCreateForm());
    
    $split->echoBody();
  }
}

?>
