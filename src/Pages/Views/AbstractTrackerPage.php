<?php
declare(strict_types = 1);

namespace Pages\Views;

use Pages\Models\BasicTrackerPageModel;

abstract class AbstractTrackerPage extends AbstractPage{
  private BasicTrackerPageModel $model;
  
  public function __construct(BasicTrackerPageModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getTitle(): string{
    return $this->model->getTracker()->getNameSafe().' - '.$this->getHeading().' - Lightning Tracker';
  }
}

?>
