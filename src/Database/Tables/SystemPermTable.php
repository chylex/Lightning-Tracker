<?php
declare(strict_types = 1);

namespace Database\Tables;

use Database\AbstractTable;
use Database\Objects\RoleInfo;
use Database\Objects\UserProfile;
use Exception;
use PDO;
use PDOException;
use Session\Permissions\SystemPermissions;

final class SystemPermTable extends AbstractTable{
  private const GUEST_PERMS = [SystemPermissions::LIST_VISIBLE_PROJECTS];
  private const LOGON_PERMS = [SystemPermissions::LIST_VISIBLE_PROJECTS];
  
  /**
   * @param string $title
   * @param array $perms
   * @param bool $special
   * @throws Exception
   */
  public function addRole(string $title, array $perms, bool $special = false): void{
    $this->db->beginTransaction();
    
    try{
      $this->execute('INSERT INTO system_roles (title, special) VALUES (?, ?)',
                     'SB', [$title, $special]);
      
      if (!empty($perms)){
        $id = $this->getLastInsertId();
        
        if ($id === null){
          $this->db->rollBack();
          throw new Exception('Could not retrieve role ID.');
        }
        
        $this->addRolePermissions($id, $perms);
      }
      
      $this->db->commit();
    }catch(PDOException $e){
      $this->db->rollBack();
      throw $e;
    }
  }
  
  public function editRole(int $id, string $title, array $perms): void{
    $this->db->beginTransaction();
    
    try{
      $this->execute('UPDATE system_roles SET title = ? WHERE id = ?',
                     'SI', [$title, $id]);
      
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
   * @param array $perms
   */
  private function addRolePermissions(int $id, array $perms): void{
    if (empty($perms)){
      return;
    }
    
    $sql = 'INSERT INTO system_role_permissions (role_id, permission) VALUES ()';
    $values = implode(',', array_map(fn($ignore): string => '(?, ?)', $perms));
    
    $stmt = $this->db->prepare(str_replace('()', $values, $sql));
    
    foreach($perms as $i => $perm){
      $stmt->bindValue(($i * 2) + 1, $id, PDO::PARAM_INT);
      $stmt->bindValue(($i * 2) + 2, $perm);
    }
    
    $stmt->execute();
  }
  
  /**
   * @return RoleInfo[]
   */
  public function listRoles(): array{
    $stmt = $this->db->query('SELECT id, title, special FROM system_roles ORDER BY special DESC, id ASC');
    return $this->fetchMap($stmt, fn($v): RoleInfo => new RoleInfo($v['id'], $v['title'], 0, (bool)$v['special']));
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
  
  public function getRoleTitleIfNotSpecial(int $id): ?string{
    $stmt = $this->execute('SELECT title FROM system_roles WHERE id = ? AND special = FALSE',
                           'I', [$id]);
    
    return $this->fetchOneColumn($stmt);
  }
  
  public function deleteById(int $id): void{
    $this->execute('DELETE FROM system_roles WHERE id = ? AND special = FALSE',
                   'I', [$id]);
  }
}

?>
