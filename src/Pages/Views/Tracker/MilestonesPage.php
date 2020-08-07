<?php
declare(strict_types = 1);

namespace Pages\Views\Tracker;

use Pages\Components\DateTimeComponent;
use Pages\Components\Forms\FormComponent;
use Pages\Components\ProgressBarComponent;
use Pages\Components\Table\TableComponent;
use Pages\Models\Tracker\MilestonesModel;
use Pages\Views\AbstractTrackerPage;

class MilestonesPage extends AbstractTrackerPage{
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
  
  protected function echoPageHead(){
    TableComponent::echoHead();
    FormComponent::echoHead();
    ProgressBarComponent::echoHead();
    DateTimeComponent::echoHead();
  }
  
  /** @noinspection HtmlMissingClosingTag */
  protected function echoPageBody(): void{
    echo <<<HTML
<div class="split-wrapper">
  <div class="split-75">
HTML;
    
    $this->model->getMilestoneTable()->echoBody();
    
    echo <<<HTML
  </div>
  <div class="split-25 min-width-250">
HTML;
    
    if ($this->model->getCreateForm() !== null){
      $this->model->getCreateForm()->echoBody();
    }
    
    echo <<<HTML
  </div>
</div>
HTML;
  }
}

?>
