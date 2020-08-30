<?php
declare(strict_types = 1);

namespace Update\Tasks;

use PDO;
use Update\AbstractMigrationTask;

final class DropAllForeignKeysTask extends AbstractMigrationTask{
  public function execute(PDO $db): void{
    $stmt = $db->prepare(<<<SQL
SELECT DISTINCT TABLE_NAME AS tbl, CONSTRAINT_NAME AS constr
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = :db_name AND REFERENCED_TABLE_SCHEMA = TABLE_SCHEMA
SQL
    );
    
    $stmt->bindValue('db_name', DB_NAME);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    
    foreach($rows as $row){
      /** @noinspection SqlResolve */
      $db->exec('ALTER TABLE `'.$row['tbl'].'` DROP FOREIGN KEY `'.$row['constr'].'`');
    }
  }
}

?>
