<?php
declare(strict_types = 1);

namespace Pages\Views\Project;

use Pages\Components\Forms\FormComponent;
use Pages\Models\Project\MilestoneEditModel;
use Pages\Views\AbstractProjectPage;

class MilestoneEditPage extends AbstractProjectPage{
  private MilestoneEditModel $model;
  
  public function __construct(MilestoneEditModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getSubtitle(): string{
    return 'Milestones';
  }
  
  protected function getHeading(): string{
    $name = $this->model->hasMilestone() ? ' - '.$this->model->getMilestoneTitleSafe() : '';
    return self::breadcrumb($this->model->getReq(), 'milestones').'Edit Milestone'.$name;
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_COMPACT;
  }
  
  protected function echoPageHead(): void{
    FormComponent::echoHead();
  }
  
  protected function echoPageBody(): void{
    if ($this->model->hasMilestone()){
      echo '<div class="max-width-400">';
      $this->model->getEditForm()->echoBody();
      echo '</div>';
    }
    else{
      echo '<p>Milestone not found.</p>';
    }
  }
}

?>
