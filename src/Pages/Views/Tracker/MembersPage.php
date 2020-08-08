<?php
declare(strict_types = 1);

namespace Pages\Views\Tracker;

use Pages\Components\Forms\FormComponent;
use Pages\Components\SplitComponent;
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
  
  protected function echoPageHead(): void{
    SplitComponent::echoHead();
    TableComponent::echoHead();
    FormComponent::echoHead();
  }
  
  protected function echoPageBody(): void{
    $split = new SplitComponent(75);
    $split->collapseAt(800, true);
    $split->setRightWidthLimits(250, 400);
    
    $split->addLeft($this->model->getMemberTable());
    $split->addRightIfNotNull($this->model->getInviteForm());
    
    $split->echoBody();
  }
}

?>
