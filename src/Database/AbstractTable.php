<?php
declare(strict_types = 1);

namespace Database;

use PDO;
use PDOStatement;

abstract class AbstractTable{
  protected PDO $db;
  
  public function __construct(PDO $db){
    $this->db = $db;
  }
  
  /**
   * Fetches the next result.
   * @param PDOStatement $stmt
   * @return mixed
   */
  protected function fetchNext(PDOStatement $stmt){
    return $stmt->fetch();
  }
  
  /**
   * Fetches the next result.
   * @param PDOStatement $stmt
   * @return mixed
   */
  protected function fetchNextColumn(PDOStatement $stmt){
    return $stmt->fetchColumn();
  }
  
  /**
   * Fetches one result and closes the cursor.
   * @param PDOStatement $stmt
   * @return mixed
   */
  protected function fetchOne(PDOStatement $stmt){
    $result = $stmt->fetch();
    $stmt->closeCursor();
    return $result;
  }
  
  /**
   * Fetches one result and closes the cursor.
   * @param PDOStatement $stmt
   * @return mixed
   */
  protected function fetchOneColumn(PDOStatement $stmt){
    $result = $stmt->fetchColumn();
    $stmt->closeCursor();
    return $result;
  }
}

?>
