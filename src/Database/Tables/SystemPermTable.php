<?php
declare(strict_types = 1);

namespace Database\Tables;

use Database\AbstractTable;
use Database\Objects\RoleInfo;
use Database\Objects\UserProfile;
use PDO;

final class SystemPermTable extends AbstractTable{
  public function __construct(PDO $db){
    parent::__construct($db);
  }
  
  public function addRole(string $title, array $perms): void{
    $this->db->beginTransaction();
    
    $stmt = $this->db->prepare('INSERT INTO system_roles (title) VALUES (?)');
    $stmt->execute([$title]);
    
    if (!empty($perms)){
      $values = implode(',', array_map(fn($ignore): string => '(LAST_INSERT_ID(), ?)', $perms));
      $stmt = $this->db->prepare('INSERT INTO system_role_perms (role_id, permission) VALUES '.$values);
      
      for($i = 0, $count = count($perms); $i < $count; $i++){
        $stmt->bindValue($i + 1, $perms[$i]);
      }
      
      $stmt->execute();
    }
    
    $this->db->commit();
  }
  
  /**
   * @return RoleInfo[]
   */
  public function listRoles(): array{
    $stmt = $this->db->prepare('SELECT id, title FROM system_roles ORDER BY id ASC');
    $stmt->execute();
    
    $results = [];
    
    while(($res = $this->fetchNext($stmt)) !== false){
      $results[] = new RoleInfo($res['id'], $res['title']);
    }
    
    return $results;
  }
  
  /**
   * @param ?UserProfile $user
   * @return string[]
   */
  public function listPerms(?UserProfile $user): array{
    $role_id = $user === null ? null : $user->getRoleId();
    
    if ($role_id === null){
      return []; // TODO guest permissions
    }
    
    $stmt = $this->db->prepare('SELECT permission FROM system_role_perms WHERE role_id = ?');
    $stmt->bindValue(1, $role_id, PDO::PARAM_INT);
    $stmt->execute();
  
    $results = [];
  
    while(($res = $this->fetchNextColumn($stmt)) !== false){
      $results[] = $res;
    }
  
    return $results;
  }
  
  public function deleteById(int $id): void{
    $stmt = $this->db->prepare('DELETE FROM system_roles WHERE id = ?');
    $stmt->bindValue(1, $id, PDO::PARAM_INT);
    $stmt->execute();
  }
}

?>
