<?php
declare(strict_types = 1);

namespace Update;

use Update\Tasks\SqlTask;

abstract class AbstractMigrationProcess{
  protected static final function sql(string $sql): SqlTask{
    return new SqlTask($sql);
  }
  
  /**
   * @return AbstractMigrationTask[]
   */
  public abstract function getTasks(): array;
}

?>
