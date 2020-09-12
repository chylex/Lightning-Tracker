<?php
declare(strict_types = 1);

namespace Database\Tables;

use Database\AbstractTable;
use Database\Objects\UserProfile;
use PDO;
use PDOException;
use Session\Permissions\SystemPermissions;

class SystemRolePermTable extends AbstractTable{
  private const LOGON_PERMS = [SystemPermissions::LIST_VISIBLE_PROJECTS];
  private const GUEST_PERMS = [SystemPermissions::LIST_VISIBLE_PROJECTS];
  
  /**
   * @param int $id
   * @param string[] $perms
   */
  public function addRolePermissions(int $id, array $perms): void{
    if (empty($perms)){
      return;
    }
    
    $sql = 'INSERT INTO system_role_permissions (role_id, permission) VALUES ()';
    $values = implode(',', array_map(static fn($ignore): string => '(?, ?)', $perms));
    
    $stmt = $this->db->prepare(str_replace('()', $values, $sql));
    
    foreach($perms as $i => $perm){
      $stmt->bindValue(($i * 2) + 1, $id, PDO::PARAM_INT);
      $stmt->bindValue(($i * 2) + 2, $perm);
    }
    
    $stmt->execute();
  }
  
  /**
   * @param int $id
   * @param string[] $perms
   */
  public function replaceRolePermissions(int $id, array $perms): void{
    $this->db->beginTransaction();
    
    try{
      $this->execute('DELETE FROM system_role_permissions WHERE role_id = ?',
                     'I', [$id]);
      
      $this->addRolePermissions($id, $perms);
      $this->db->commit();
    }catch(PDOException $e){
      $this->db->rollBack();
      throw $e;
    }
  }
  
  /**
   * @param int $id
   * @return string[]
   */
  public function listRolePerms(int $id): array{
    $stmt = $this->execute('SELECT permission FROM system_role_permissions WHERE role_id = ?',
                           'I', [$id]);
    
    $perms = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return $perms === false ? [] : $perms;
  }
  /**
   * @param ?UserProfile $user
   * @return string[]
   */
  public function listUserPerms(?UserProfile $user): array{
    if ($user === null){
      return self::GUEST_PERMS;
    }
    
    if ($user->getSystemRoleId() === null){
      return self::LOGON_PERMS;
    }
    
    $stmt = $this->execute('SELECT permission FROM system_role_permissions WHERE role_id = ?',
                           'I', [$user->getSystemRoleId()]);
    
    $perms = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return $perms === false ? [] : $perms;
  }
}

?>
