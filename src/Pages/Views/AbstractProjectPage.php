<?php
declare(strict_types = 1);

namespace Pages\Views;

use Pages\Models\BasicProjectPageModel;

abstract class AbstractProjectPage extends AbstractPage{
  private BasicProjectPageModel $model;
  
  public function __construct(BasicProjectPageModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected final function getTitle(): string{
    return $this->model->getProject()->getNameSafe().' - '.$this->getSubtitle().' - Lightning Tracker';
  }
}

?>
