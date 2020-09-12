<?php
declare(strict_types = 1);

namespace Database\Tables;

use Database\AbstractProjectTable;
use Database\Objects\UserProfile;
use PDO;
use PDOException;

final class ProjectRolePermTable extends AbstractProjectTable{
  /**
   * @param int $id
   * @param string[] $perms
   */
  public function addRolePermissions(int $id, array $perms): void{
    if (empty($perms)){
      return;
    }
    
    $project = $this->getProjectId();
    
    $sql = 'INSERT INTO project_role_permissions (project_id, role_id, permission) VALUES ()';
    $values = implode(',', array_map(static fn($ignore): string => '(?, ?, ?)', $perms));
    
    $stmt = $this->db->prepare(str_replace('()', $values, $sql));
    
    foreach($perms as $i => $perm){
      $stmt->bindValue(($i * 3) + 1, $project, PDO::PARAM_INT);
      $stmt->bindValue(($i * 3) + 2, $id, PDO::PARAM_INT);
      $stmt->bindValue(($i * 3) + 3, $perm);
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
      $this->execute('DELETE FROM project_role_permissions WHERE role_id = ? AND project_id = ?',
                     'II', [$id, $this->getProjectId()]);
      
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
    $stmt = $this->execute('SELECT permission FROM project_role_permissions WHERE role_id = ? AND project_id = ?',
                           'II', [$id, $this->getProjectId()]);
    
    $perms = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return $perms === false ? [] : $perms;
  }
  
  /**
   * @param ?UserProfile $user
   * @return string[]
   */
  public function listUserPerms(?UserProfile $user): array{
    if ($user === null){
      return [];
    }
    
    $sql = <<<SQL
SELECT prp.permission
FROM project_role_permissions prp
JOIN project_members pm ON prp.role_id = pm.role_id AND prp.project_id = pm.project_id
WHERE pm.user_id = ? AND pm.project_id = ?
SQL;
    
    $stmt = $this->execute($sql, 'SI', [$user->getId(), $this->getProjectId()]);
    
    $perms = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return $perms === false ? [] : $perms;
  }
}

?>
