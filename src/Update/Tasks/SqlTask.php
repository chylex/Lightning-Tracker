<?php
declare(strict_types = 1);

namespace Update\Tasks;

use PDO;
use Update\AbstractMigrationTask;

final class SqlTask extends AbstractMigrationTask{
  private string $sql;
  
  public function __construct(string $sql){
    $this->sql = $sql;
  }
  
  public function execute(PDO $db): void{
    $db->exec($this->sql);
  }
}

?>
