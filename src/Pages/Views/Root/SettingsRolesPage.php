<?php
declare(strict_types = 1);

namespace Pages\Views\Root;

use Pages\Components\SplitComponent;
use Pages\Components\Table\TableComponent;
use Pages\Components\TitledSectionComponent;
use Pages\IViewable;
use Pages\Models\Root\SettingsRolesModel;

class SettingsRolesPage extends AbstractSettingsPage{
  private SettingsRolesModel $model;
  
  public function __construct(SettingsRolesModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getSubtitle(): string{
    return parent::getSubtitle().' - Roles';
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_FULL;
  }
  
  protected function echoPageHead(): void{
    parent::echoPageHead();
    TableComponent::echoHead();
  }
  
  protected function getSettingsPageColumn(): IViewable{
    $split = new SplitComponent(75);
    $split->collapseAt(1024, true);
    $split->setRightWidthLimits(250, 500);
    
    $split->addLeft($this->model->createRoleTable());
    $split->addRight(new TitledSectionComponent('Create Role', $this->model->getCreateForm()));
    
    return $split;
  }
}

?>
