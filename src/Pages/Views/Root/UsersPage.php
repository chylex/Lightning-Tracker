<?php
declare(strict_types = 1);

namespace Pages\Views\Root;

use Pages\Components\DateTimeComponent;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Table\TableComponent;
use Pages\Models\Root\UsersModel;
use Pages\Views\AbstractPage;

class UsersPage extends AbstractPage{
  private UsersModel $model;
  
  public function __construct(UsersModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getHeading(): string{
    return 'Users';
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_FULL;
  }
  
  protected function echoPageHead(): void{
    TableComponent::echoHead();
    FormComponent::echoHead();
    DateTimeComponent::echoHead();
  }
  
  /** @noinspection HtmlMissingClosingTag */
  protected function echoPageBody(): void{
    echo <<<HTML
<div class="split-wrapper">
  <div class="split-75">
HTML;
    
    $this->model->getUserTable()->echoBody();
    
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
