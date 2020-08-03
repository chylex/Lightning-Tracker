<?php
declare(strict_types = 1);

namespace Pages\Models;

use Pages\Components\Navigation\NavigationComponent;
use Pages\IModel;

abstract class AbstractWrapperModel implements IModel{
  private IModel $model;
  
  public function __construct(IModel $model){
    $this->model = $model;
  }
  
  public function load(): IModel{
    $this->model->load();
    return $this;
  }
  
  public function ensureLoaded(): void{
    $this->model->ensureLoaded();
  }
  
  public function getNav(): NavigationComponent{
    return $this->model->getNav();
  }
}

?>
