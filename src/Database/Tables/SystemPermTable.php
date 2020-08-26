<?php
declare(strict_types = 1);

namespace Database\Tables;

use Database\AbstractTable;
use Database\Objects\RoleInfo;
use Database\Objects\UserProfile;
use PDO;
use PDOException;
use Session\Permissions\SystemPermissions;

final class SystemPermTable extends AbstractTable{
  private const GUEST_PERMS = [SystemPermissions::LIST_PUBLIC_TRACKERS];
  private const LOGON_PERMS = [SystemPermissions::LIST_PUBLIC_TRACKERS];
  
  public function __construct(PDO $db){
    parent::__construct($db);
  }
  
  public function addRole(string $title, array $perms, bool $special = false): void{
    $this->db->beginTransaction();
    
    try{
      $stmt = $this->db->prepare('INSERT INTO system_roles (title, special) VALUES (?, ?)');
      $stmt->bindValue(1, $title);
      $stmt->bindValue(2, $special, PDO::PARAM_BOOL);
      $stmt->execute();
      
      if (!empty($perms)){
        $sql = 'INSERT INTO system_role_perms (role_id, permission) VALUES ()';
        $values = implode(',', array_map(fn($ignore): string => '(LAST_INSERT_ID(), ?)', $perms));
        
        $stmt = $this->db->prepare(str_replace('()', $values, $sql));
        
        for($i = 0, $count = count($perms); $i < $count; $i++){
          $stmt->bindValue($i + 1, $perms[$i]);
        }
        
        $stmt->execute();
      }
      
      $this->db->commit();
    }catch(PDOException $e){
      $this->db->rollBack();
      throw $e;
    }
  }
  
  /**
   * @return RoleInfo[]
   */
  public function listRoles(): array{
    $stmt = $this->db->prepare('SELECT id, title, special FROM system_roles ORDER BY special DESC, id ASC');
    $stmt->execute();
    
    $results = [];
    
    while(($res = $this->fetchNext($stmt)) !== false){
      $results[] = new RoleInfo($res['id'], $res['title'], 0, (bool)$res['special']);
    }
    
    return $results;
  }
  
  /**
   * @param ?UserProfile $user
   * @return string[]
   */
  public function listPerms(?UserProfile $user): array{
    if ($user === null){
      return self::GUEST_PERMS;
    }
    
    if ($user->getSystemRoleId() === null){
      return self::LOGON_PERMS;
    }
    
    $stmt = $this->db->prepare('SELECT permission FROM system_role_perms WHERE role_id = ?');
    $stmt->bindValue(1, $user->getSystemRoleId(), PDO::PARAM_INT);
    $stmt->execute();
    
    $perms = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return $perms === false ? [] : $perms;
  }
  
  public function deleteById(int $id): void{
    $stmt = $this->db->prepare('DELETE FROM system_roles WHERE id = ? AND special = FALSE');
    $stmt->bindValue(1, $id, PDO::PARAM_INT);
    $stmt->execute();
  }
}

?>
