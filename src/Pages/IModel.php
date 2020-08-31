<?php
declare(strict_types = 1);

namespace Pages;

use Pages\Components\Navigation\NavigationComponent;
use Routing\Request;

interface IModel{
  /**
   * Loads data into the model.
   *
   * @return $this
   */
  public function load(): IModel;
  public function ensureLoaded(): void;
  
  public function getReq(): Request;
  public function getNav(): NavigationComponent;
}

?>
