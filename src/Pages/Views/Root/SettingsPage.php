<?php
declare(strict_types = 1);

namespace Pages\Views\Root;

use Pages\Components\Forms\FormComponent;
use Pages\Models\Root\SettingsModel;
use Pages\Views\AbstractPage;

class SettingsPage extends AbstractPage{
  private SettingsModel $model;
  
  public function __construct(SettingsModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getHeading(): string{
    return 'Settings';
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_CONDENSED;
  }
  
  protected function echoPageHead(){
    FormComponent::echoHead();
  }
  
  protected function echoPageBody(): void{
    $this->model->getForm()->echoBody();
  }
}

?>
