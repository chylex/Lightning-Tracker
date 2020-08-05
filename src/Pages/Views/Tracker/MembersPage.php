<?php
declare(strict_types = 1);

namespace Pages\Views\Tracker;

use Pages\Components\Forms\FormComponent;
use Pages\Components\Table\TableComponent;
use Pages\Models\Tracker\MembersModel;
use Pages\Views\AbstractTrackerPage;

class MembersPage extends AbstractTrackerPage{
  private MembersModel $model;
  
  public function __construct(MembersModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getHeading(): string{
    return 'Members';
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_FULL;
  }
  
  protected function echoPageHead(){
    TableComponent::echoHead();
    FormComponent::echoHead();
  }
  
  /** @noinspection HtmlMissingClosingTag */
  protected function echoPageBody(): void{
    echo <<<HTML
<div class="split-wrapper">
  <div class="split-75">
HTML;
    
    $this->model->getMemberTable()->echoBody();
    
    echo <<<HTML
  </div>
  <div class="split-25 min-width-250">
HTML;
    
    if ($this->model->getInviteForm() !== null){
      $this->model->getInviteForm()->echoBody();
    }
    
    echo <<<HTML
  </div>
</div>
HTML;
  }
}

?>
