<?php
declare(strict_types = 1);

namespace Pages\Views\Root;

use Pages\Components\DateTimeComponent;
use Pages\Components\Forms\FormComponent;
use Pages\Components\SplitComponent;
use Pages\Components\Table\TableComponent;
use Pages\Components\TitledSectionComponent;
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
    SplitComponent::echoHead();
    TableComponent::echoHead();
    FormComponent::echoHead();
    DateTimeComponent::echoHead();
  }
  
  protected function echoPageBody(): void{
    $split = new SplitComponent(75);
    $split->collapseAt(800, true);
    $split->setRightWidthLimits(250, 500);
    
    $split->addLeft($this->model->createUserTable());
    $split->addRightIfNotNull(TitledSectionComponent::wrap('Create User', $this->model->getCreateForm()));
    
    $split->echoBody();
  }
}

?>
