<?php
declare(strict_types = 1);

namespace Update\Tasks;

use PDO;
use Update\AbstractMigrationTask;

final class SqlTransactionTask extends AbstractMigrationTask{
  /**
   * @var string[]
   */
  private array $statements;
  
  public function __construct(array $statements){
    $this->statements = $statements;
  }
  
  public function prepare(PDO $db): void{
    $db->beginTransaction();
  }
  
  public function execute(PDO $db): void{
    foreach($this->statements as $sql){
      $db->exec($sql);
    }
  }
  
  public function finalize(PDO $db): void{
    $db->commit();
  }
}

?>
