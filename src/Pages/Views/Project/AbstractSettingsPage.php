<?php
declare(strict_types = 1);

namespace Pages\Views\Project;

use Pages\Components\Forms\FormComponent;
use Pages\Components\Html;
use Pages\Components\Sidemenu\SidemenuComponent;
use Pages\Components\SplitComponent;
use Pages\IViewable;
use Pages\Models\Project\AbstractSettingsModel;
use Pages\Views\AbstractProjectPage;

abstract class AbstractSettingsPage extends AbstractProjectPage{
  private AbstractSettingsModel $model;
  
  public function __construct(AbstractSettingsModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getSubtitle(): string{
    return 'Settings';
  }
  
  protected final function getHeading(): string{
    return '';
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_CONDENSED;
  }
  
  protected function echoPageHead(): void{
    SplitComponent::echoHead();
    SidemenuComponent::echoHead();
    FormComponent::echoHead();
  }
  
  protected final function echoPageBody(): void{
    $split = new SplitComponent(20);
    $split->collapseAt(640);
    $split->setLeftWidthLimits(200, 250);
    
    $split->addLeft(new Html('<h3>Menu</h3>'));
    $split->addLeft($this->model->getMenuLinks());
    $split->addRight($this->getSettingsPageColumn());
    
    $split->echoBody();
  }
  
  protected abstract function getSettingsPageColumn(): IViewable;
}

?>
