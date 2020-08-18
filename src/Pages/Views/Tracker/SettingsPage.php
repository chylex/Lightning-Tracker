<?php
declare(strict_types = 1);

namespace Pages\Views\Tracker;

use Pages\Components\Forms\FormComponent;
use Pages\Models\Tracker\SettingsModel;
use Pages\Views\AbstractTrackerPage;

class SettingsPage extends AbstractTrackerPage{
  private SettingsModel $model;
  
  public function __construct(SettingsModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getHeading(): string{
    return 'Settings';
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_MINIMAL;
  }
  
  protected function echoPageHead(): void{
    FormComponent::echoHead();
  }
  
  protected function echoPageBody(): void{
    $this->model->getForm()->echoBody();
  }
}

?>
