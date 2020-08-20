<?php
declare(strict_types = 1);

namespace Database\Tables\Traits;

use Database\Objects\RoleInfo;
use PDO;
use PDOStatement;

trait PermTable{
  protected abstract function getDB(): PDO;
  protected abstract function fetchNext(PDOStatement $stmt);
  protected abstract function fetchNextColumn(PDOStatement $stmt);
  
  protected final function addPermissions(string $sql_base, array $perms): void{
    if (empty($perms)){
      return;
    }
    
    $values = implode(',', array_map(fn($ignore): string => '(LAST_INSERT_ID(), ?)', $perms));
    $stmt = $this->getDB()->prepare(str_replace('()', $values, $sql_base));
    
    for($i = 0, $count = count($perms); $i < $count; $i++){
      $stmt->bindValue($i + 1, $perms[$i]);
    }
    
    $stmt->execute();
  }
  
  /**
   * @param PDOStatement $stmt
   * @return RoleInfo[]
   */
  protected final function fetchRoles(PDOStatement $stmt): array{
    $results = [];
    
    while(($res = $this->fetchNext($stmt)) !== false){
      $results[] = new RoleInfo($res['id'], $res['title']);
    }
    
    return $results;
  }
  
  /**
   * @param PDOStatement $stmt
   * @return string[]
   */
  protected final function fetchPerms(PDOStatement $stmt): array{
    $results = [];
    
    while(($res = $this->fetchNextColumn($stmt)) !== false){
      $results[] = $res;
    }
    
    return $results;
  }
}

?>
