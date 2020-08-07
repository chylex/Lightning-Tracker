<?php
declare(strict_types = 1);

namespace Pages\Views\Root;

use Pages\Components\Forms\FormComponent;
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
    TableComponent::echoHead();
    FormComponent::echoHead();
  }
  
  /** @noinspection HtmlMissingClosingTag */
  protected function echoPageBody(): void{
    echo <<<HTML
<div class="split-wrapper">
  <div class="split-75">
HTML;
    
    $this->model->getTrackerTable()->echoBody();
    
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
