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
  
  protected function getTitle(): string{
    return $this->model->getTracker()->getNameSafe().' - Milestones - Lightning Tracker';
  }
  
  protected function getHeading(): string{
    $base_url = $this->model->getReq()->getBasePath()->encoded();
    return '<a href="'.$base_url.'/milestones">Back</a> <span class="breadcrumb-arrows">&raquo;</span> Edit Milestone';
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
