<?php
declare(strict_types = 1);

namespace Pages\Views\Project;

use Pages\Components\TitledSectionComponent;
use Pages\IViewable;
use Pages\Models\Project\SettingsGeneralModel;

class SettingsGeneralPage extends AbstractSettingsPage{
  private SettingsGeneralModel $model;
  
  public function __construct(SettingsGeneralModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getSettingsPageColumn(): IViewable{
    return new TitledSectionComponent('Project', $this->model->getSettingsForm());
  }
}

?>
