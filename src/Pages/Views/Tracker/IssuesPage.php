<?php
declare(strict_types = 1);

namespace Pages\Views\Tracker;

use Pages\Components\Forms\FormComponent;
use Pages\Components\ProgressBarComponent;
use Pages\Components\Sidemenu\SidemenuComponent;
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
    TableComponent::echoHead();
    FormComponent::echoHead();
    SidemenuComponent::echoHead();
    ProgressBarComponent::echoHead();
    
    echo <<<HTML
<link rel="stylesheet" type="text/css" href="~resources/css/issues.css">
HTML;
  }
  
  /** @noinspection HtmlMissingClosingTag */
  protected function echoPageBody(): void{
    echo <<<HTML
<div class="split-wrapper">
  <div class="split-80">
HTML;
    
    $this->model->getIssueTable()->echoBody();
    
    echo <<<HTML
  </div>
  <div class="split-20 min-width-250">
HTML;
    
    $this->model->getMenuActions()->echoBody();
    
    echo <<<HTML
  </div>
</div>
HTML;
  }
}

?>
