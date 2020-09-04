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
  
  protected final function getLastInsertId(): ?int{
    $stmt = $this->db->query('SELECT LAST_INSERT_ID()');
    $stmt->execute();
    return $this->fetchOneInt($stmt);
  }
  
  /**
   * Fetches all results, applies a mapping function to each result, and returns an array of the mapped results.
   *
   * @param PDOStatement $stmt
   * @param callable $mapper
   * @return array
   */
  protected function fetchMap(PDOStatement $stmt, callable $mapper): array{
    $results = [];
    
    while(($res = $stmt->fetch()) !== false){
      $results[] = $mapper($res);
    }
    
    return $results;
  }
  
  /**
   * Fetches one result and closes the cursor. Returns the raw value from PDOStatement::fetch without any coercion.
   *
   * @param PDOStatement $stmt
   * @return mixed
   */
  protected function fetchOneRaw(PDOStatement $stmt){
    $result = $stmt->fetch();
    $stmt->closeCursor();
    return $result;
  }
  
  /**
   * Fetches one result and closes the cursor. Coerces 'false' results into 'null'.
   *
   * @param PDOStatement $stmt
   * @return mixed
   */
  protected function fetchOneColumn(PDOStatement $stmt){
    $result = $stmt->fetchColumn();
    $stmt->closeCursor();
    return $result === false ? null : $result;
  }
  
  /**
   * Fetches one result and closes the cursor. Returns the raw value from PDOStatement::fetchColumn without any coercion.
   *
   * @param PDOStatement $stmt
   * @return mixed
   */
  protected function fetchOneColumnRaw(PDOStatement $stmt){
    $result = $stmt->fetchColumn();
    $stmt->closeCursor();
    return $result;
  }
  
  /**
   * Fetches one integer result and closes the cursor. Coerces 'false' results into 'null'.
   *
   * @param PDOStatement $stmt
   * @return mixed
   */
  protected function fetchOneInt(PDOStatement $stmt): ?int{
    $result = $stmt->fetchColumn();
    $stmt->closeCursor();
    return $result === false || $result === null ? null : (int)$result;
  }
}

?>
