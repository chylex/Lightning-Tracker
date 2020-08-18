<?php
declare(strict_types = 1);

namespace Pages\Views\Tracker;

use Pages\Components\Forms\FormComponent;
use Pages\Models\Tracker\MilestoneEditModel;
use Pages\Views\AbstractTrackerPage;

class MilestoneEditPage extends AbstractTrackerPage{
  private MilestoneEditModel $model;
  
  public function __construct(MilestoneEditModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getSubtitle(): string{
    return 'Milestones';
  }
  
  protected function getHeading(): string{
    return self::breadcrumb($this->model->getReq(), '/milestones').'Edit Milestone';
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_COMPACT;
  }
  
  protected function echoPageHead(): void{
    FormComponent::echoHead();
  }
  
  protected function echoPageBody(): void{
    if ($this->model->hasMilestone()){
      $this->model->getEditForm()->echoBody();
    }
    else{
      echo '<p>Milestone not found.</p>';
    }
  }
}

?>
