<?php
declare(strict_types = 1);

namespace Update;

use PDO;

abstract class AbstractMigrationTask{
  public function prepare(PDO $db): void{
  }
  
  public abstract function execute(PDO $db): void;
  
  public function finalize(PDO $db): void{
  }
}

?>
