<?php
declare(strict_types = 1);

namespace Update;

use Update\Tasks\SqlTask;
use Update\Tasks\SqlTransactionTask;

abstract class AbstractMigrationProcess{
  protected static final function sql(string $sql): SqlTask{
    return new SqlTask($sql);
  }
  
  protected static final function transaction(string...$sql): SqlTransactionTask{
    return new SqlTransactionTask($sql);
  }
  
  /**
   * @return AbstractMigrationTask[]
   */
  public abstract function getTasks(): array;
}

?>
