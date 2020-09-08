<?php
declare(strict_types = 1);

namespace Pages\Views\Project;

use Pages\Components\Forms\Elements\FormLightMarkEditor;
use Pages\Components\TitledSectionComponent;
use Pages\IViewable;
use Pages\Models\Project\SettingsDescriptionModel;

class SettingsDescriptionPage extends AbstractSettingsPage{
  private SettingsDescriptionModel $model;
  
  public function __construct(SettingsDescriptionModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function echoPageHead(): void{
    parent::echoPageHead();
    FormLightMarkEditor::echoHead();
  }
  
  protected function getSettingsPageColumn(): IViewable{
    return new TitledSectionComponent('Description', $this->model->getEditDescriptionForm());
  }
}

?>
