<?php
declare(strict_types = 1);

namespace Pages\Views\Root;

use Pages\IViewable;
use Pages\Models\Root\SettingsGeneralModel;

class SettingsGeneralPage extends AbstractSettingsPage{
  private SettingsGeneralModel $model;
  
  public function __construct(SettingsGeneralModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getSettingsPageColumn(): IViewable{
    return $this->model->getSettingsForm();
  }
}

?>
