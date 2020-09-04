<?php
declare(strict_types = 1);

namespace Database\Tables;

use Data\UserId;
use Database\AbstractProjectTable;
use Database\Objects\RoleInfo;
use Database\Objects\UserProfile;
use Exception;
use LogicException;
use PDO;
use PDOException;

final class ProjectPermTable extends AbstractProjectTable{
  /**
   * @param string $title
   * @param array $perms
   * @param bool $special
   * @return int
   * @throws Exception
   */
  public function addRole(string $title, array $perms, bool $special = false): int{
    $owned_transaction = !$this->db->inTransaction();
    $project = $this->getProjectId();
    
    if ($owned_transaction){
      $this->db->beginTransaction();
    }
    
    try{
      $stmt = $this->db->prepare(<<<SQL
SELECT IFNULL(MAX(role_id) + 1, 1)  AS id,
       IFNULL(MAX(ordering) + 1, 1) AS ordering
FROM project_roles
WHERE project_id = ?
SQL
      );
      
      $stmt->bindValue(1, $project, PDO::PARAM_INT);
      $stmt->execute();
      
      $next = $this->fetchOneRaw($stmt);
      
      if ($next === false){
        $this->db->rollBack();
        throw new LogicException('Error calculating next role ID.');
      }
      
      $role_id = $next['id'];
      
      $stmt = $this->db->prepare('INSERT INTO project_roles (project_id, role_id, title, ordering, special) VALUES (?, ?, ?, ?, ?)');
      $stmt->bindValue(1, $project, PDO::PARAM_INT);
      $stmt->bindValue(2, $role_id, PDO::PARAM_INT);
      $stmt->bindValue(3, $title);
      $stmt->bindValue(4, $special ? 0 : $next['ordering'], PDO::PARAM_INT);
      $stmt->bindValue(5, $special, PDO::PARAM_BOOL);
      $stmt->execute();
      
      $this->addRolePermissions($role_id, $perms);
      
      if ($owned_transaction){
        $this->db->commit();
      }
      
      return $role_id;
    }catch(PDOException $e){
      if ($owned_transaction){
        $this->db->rollBack();
      }
      
      throw $e;
    }
  }
  
  public function editRole(int $id, string $title, array $perms): void{
    $project = $this->getProjectId();
    
    $this->db->beginTransaction();
    
    try{
      $stmt = $this->db->prepare('UPDATE project_roles SET title = ? WHERE role_id = ? AND project_id = ?');
      $stmt->bindValue(1, $title);
      $stmt->bindValue(2, $id, PDO::PARAM_INT);
      $stmt->bindValue(3, $project, PDO::PARAM_INT);
      $stmt->execute();
      
      $stmt = $this->db->prepare('DELETE FROM project_role_permissions WHERE role_id = ? AND project_id = ?');
      $stmt->bindValue(1, $id, PDO::PARAM_INT);
      $stmt->bindValue(2, $project, PDO::PARAM_INT);
      $stmt->execute();
      
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
    
    $project = $this->getProjectId();
    
    $sql = 'INSERT INTO project_role_permissions (project_id, role_id, permission) VALUES ()';
    $values = implode(',', array_map(fn($ignore): string => '(?, ?, ?)', $perms));
    
    $stmt = $this->db->prepare(str_replace('()', $values, $sql));
    
    foreach($perms as $i => $perm){
      $stmt->bindValue(($i * 3) + 1, $project, PDO::PARAM_INT);
      $stmt->bindValue(($i * 3) + 2, $id, PDO::PARAM_INT);
      $stmt->bindValue(($i * 3) + 3, $perm);
    }
    
    $stmt->execute();
  }
  
  public function moveRoleUp(int $id): void{
    $this->db->beginTransaction();
    
    try{
      $ordering = $this->getRoleOrderingIfNotSpecial($id);
      
      if ($ordering === null || $ordering <= 1 || $this->isRoleSpecialByOrdering($ordering - 1)){
        $this->db->rollBack();
        return;
      }
      
      $this->swapRolesInternal($id, $ordering, $ordering - 1);
      $this->db->commit();
    }catch(PDOException $e){
      $this->db->rollBack();
      throw $e;
    }
  }
  
  public function moveRoleDown(int $id): void{
    $this->db->beginTransaction();
    
    try{
      $limit = $this->findMaxOrdering();
      
      if ($limit === false){
        $this->db->rollBack();
        return;
      }
      
      $ordering = $this->getRoleOrderingIfNotSpecial($id);
      
      if ($ordering === null || $ordering >= $limit || $this->isRoleSpecialByOrdering($ordering + 1)){
        $this->db->rollBack();
        return;
      }
      
      $this->swapRolesInternal($id, $ordering, $ordering + 1);
      $this->db->commit();
    }catch(PDOException $e){
      $this->db->rollBack();
      throw $e;
    }
  }
  
  public function findMaxOrdering(): ?int{
    $stmt = $this->db->prepare('SELECT MAX(ordering) FROM project_roles WHERE project_id = ?');
    $stmt->bindValue(1, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->execute();
    return $this->fetchOneInt($stmt);
  }
  
  private function swapRolesInternal(int $id, int $current_ordering, int $other_ordering): void{
    $stmt = $this->db->prepare('UPDATE project_roles SET ordering = ? WHERE ordering = ? AND project_id = ?');
    $stmt->bindValue(1, $current_ordering, PDO::PARAM_INT);
    $stmt->bindValue(2, $other_ordering, PDO::PARAM_INT);
    $stmt->bindValue(3, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->execute();
    
    $stmt = $this->db->prepare('UPDATE project_roles SET ordering = ? WHERE role_id = ? AND project_id = ?');
    $stmt->bindValue(1, $other_ordering, PDO::PARAM_INT);
    $stmt->bindValue(2, $id, PDO::PARAM_INT);
    $stmt->bindValue(3, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->execute();
  }
  
  private function getRoleOrderingIfNotSpecial(int $id): ?int{
    $stmt = $this->db->prepare('SELECT ordering FROM project_roles WHERE role_id = ? AND project_id = ? AND special = FALSE');
    $stmt->bindValue(1, $id, PDO::PARAM_INT);
    $stmt->bindValue(2, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->execute();
    return $this->fetchOneInt($stmt);
  }
  
  private function isRoleSpecialByOrdering(int $ordering): bool{
    $stmt = $this->db->prepare('SELECT special FROM project_roles WHERE ordering = ? AND project_id = ?');
    $stmt->bindValue(1, $ordering, PDO::PARAM_INT);
    $stmt->bindValue(2, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->execute();
    return (bool)$this->fetchOneColumn($stmt);
  }
  
  public function isRoleAssignableBy(int $role_id, UserId $user_id): bool{
    $stmt = $this->db->prepare(<<<SQL
SELECT 1
FROM project_roles pr
WHERE role_id = ? AND project_id = ?
  AND special = FALSE
  AND ordering > IFNULL((SELECT ordering
                         FROM project_roles pr2
                         JOIN project_members pm ON pr2.role_id = pm.role_id AND pr2.project_id = pm.project_id
                         WHERE pm.user_id = ? AND pm.project_id = pr.project_id), ~0)
SQL
    );
    
    $stmt->bindValue(1, $role_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->bindValue(3, $user_id);
    $stmt->execute();
    return $this->fetchOneInt($stmt) !== null;
  }
  
  /**
   * @return RoleInfo[]
   */
  public function listRoles(): array{
    $stmt = $this->db->prepare('SELECT role_id, title, ordering, special FROM project_roles WHERE project_id = ? ORDER BY special DESC, ordering ASC');
    $stmt->bindValue(1, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->execute();
    return $this->fetchMap($stmt, fn($v): RoleInfo => new RoleInfo($v['role_id'], $v['title'], (int)$v['ordering'], (bool)$v['special']));
  }
  
  /**
   * @param UserId $user_id
   * @return RoleInfo[]
   */
  public function listRolesAssignableBy(UserId $user_id): array{
    $stmt = $this->db->prepare(<<<SQL
SELECT role_id, title, ordering, special
FROM project_roles pr
WHERE project_id = ?
  AND special = FALSE
  AND ordering > IFNULL((SELECT ordering
                         FROM project_roles pr2
                         JOIN project_members pm ON pr2.role_id = pm.role_id AND pr2.project_id = pm.project_id
                         WHERE pm.user_id = ? AND pm.project_id = pr.project_id), ~0)
ORDER BY ordering ASC
SQL
    );
    
    $stmt->bindValue(1, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->bindValue(2, $user_id);
    $stmt->bindValue(3, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->execute();
    return $this->fetchMap($stmt, fn($v): RoleInfo => new RoleInfo($v['role_id'], $v['title'], (int)$v['ordering'], (bool)$v['special']));
  }
  
  /**
   * @param int $id
   * @return string[]
   */
  public function listRolePerms(int $id): array{
    $stmt = $this->db->prepare('SELECT permission FROM project_role_permissions WHERE role_id = ? AND project_id = ?');
    $stmt->bindValue(1, $id, PDO::PARAM_INT);
    $stmt->bindValue(2, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->execute();
    
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
    
    $stmt = $this->db->prepare(<<<SQL
SELECT prp.permission
FROM project_role_permissions prp
JOIN project_members pm ON prp.role_id = pm.role_id AND prp.project_id = pm.project_id
WHERE pm.user_id = ? AND pm.project_id = ?
SQL
    );
    
    $stmt->bindValue(1, $user->getId(), PDO::PARAM_INT);
    $stmt->bindValue(2, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->execute();
    
    $perms = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return $perms === false ? [] : $perms;
  }
  
  public function getRoleTitleIfNotSpecial(int $id): ?string{
    $stmt = $this->db->prepare('SELECT title FROM project_roles WHERE role_id = ? AND project_id = ? AND special = FALSE');
    $stmt->bindValue(1, $id, PDO::PARAM_INT);
    $stmt->bindValue(2, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->execute();
    return $this->fetchOneColumn($stmt);
  }
  
  public function deleteById(int $id): void{
    $project = $this->getProjectId();
    
    $this->db->beginTransaction();
    
    try{
      $ordering = $this->getRoleOrderingIfNotSpecial($id);
      
      if ($ordering === null){
        $this->db->rollBack();
        return;
      }
      
      $stmt = $this->db->prepare('UPDATE project_members SET role_id = NULL WHERE role_id = ? AND project_id = ?');
      $stmt->bindValue(1, $id, PDO::PARAM_INT);
      $stmt->bindValue(2, $project, PDO::PARAM_INT);
      $stmt->execute();
      
      $stmt = $this->db->prepare('UPDATE project_roles SET ordering = ordering - 1 WHERE ordering > ? AND project_id = ?');
      $stmt->bindValue(1, $ordering, PDO::PARAM_INT);
      $stmt->bindValue(2, $project, PDO::PARAM_INT);
      $stmt->execute();
      
      $stmt = $this->db->prepare('DELETE FROM project_roles WHERE role_id = ? AND project_id = ? AND special = FALSE');
      $stmt->bindValue(1, $id, PDO::PARAM_INT);
      $stmt->bindValue(2, $this->getProjectId(), PDO::PARAM_INT);
      $stmt->execute();
      
      $this->db->commit();
    }catch(PDOException $e){
      $this->db->rollBack();
      throw $e;
    }
  }
}

?>
