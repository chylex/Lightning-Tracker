<?php
declare(strict_types = 1);

namespace Pages;

use Pages\Components\Navigation\NavigationComponent;

interface IModel{
  /**
   * Loads data into the model.
   *
   * @return $this
   */
  public function load(): IModel;
  
  public function ensureLoaded(): void;
  
  public function getNav(): NavigationComponent;
}

?>
