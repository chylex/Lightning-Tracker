<?php
declare(strict_types = 1);

namespace Database\Tables;

use Data\UserId;
use Database\AbstractProjectTable;
use Database\Objects\RoleInfo;
use LogicException;
use PDOException;

final class ProjectRoleTable extends AbstractProjectTable{
  /**
   * @param string $title
   * @param string $type
   * @return int
   */
  public function addRole(string $title, string $type = RoleInfo::PROJECT_NORMAL): int{
    $owned_transaction = !$this->db->inTransaction();
    $project = $this->getProjectId();
    
    if ($owned_transaction){
      $this->db->beginTransaction();
    }
    
    try{
      $sql = <<<SQL
SELECT IFNULL(MAX(role_id) + 1, 1)  AS id,
       IFNULL(MAX(ordering) + 1, 1) AS ordering
FROM project_roles
WHERE project_id = ?
SQL;
      
      $stmt = $this->execute($sql, 'I', [$project]);
      $next = $this->fetchOneRaw($stmt);
      
      if ($next === false){
        $this->db->rollBack();
        throw new LogicException('Error calculating next role ID.');
      }
      
      $role_id = $next['id'];
      
      $this->execute('INSERT INTO project_roles (project_id, role_id, type, title, ordering) VALUES (?, ?, ?, ?, ?)',
                     'IISSI', [$project, $role_id, $type, $title, $type === RoleInfo::PROJECT_NORMAL ? $next['ordering'] : 0]);
      
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
  
  public function editRole(int $id, string $title): void{
    $this->execute('UPDATE project_roles SET title = ? WHERE role_id = ? AND project_id = ?',
                   'SII', [$title, $id, $this->getProjectId()]);
  }
  
  public function swapRolesIfNormal(int $ordering1, int $ordering2): void{
    $this->db->beginTransaction();
    
    try{
      $sql = <<<SQL
UPDATE project_roles pr1 INNER JOIN project_roles pr2 ON pr1.ordering = ? AND pr2.ordering = ? AND pr1.project_id = pr2.project_id
SET pr1.ordering = -pr2.ordering,
    pr2.ordering = -pr1.ordering
WHERE pr1.project_id = ? AND pr1.type = 'normal' AND pr2.type = 'normal'
SQL;
      
      $this->execute($sql, 'III', [$ordering1, $ordering2, $this->getProjectId()]);
      
      $sql = <<<SQL
UPDATE project_roles
SET ordering = -ordering
WHERE project_id = ? AND ordering IN (-?, -?) AND type = 'normal'
SQL;
      
      $this->execute($sql, 'III', [$this->getProjectId(), $ordering1, $ordering2]);
      
      $this->db->commit();
    }catch(PDOException $e){
      $this->db->rollBack();
      throw $e;
    }
  }
  
  public function findMaxOrdering(): ?int{
    $stmt = $this->execute('SELECT MAX(ordering) FROM project_roles WHERE project_id = ?',
                           'I', [$this->getProjectId()]);
    
    return $this->fetchOneInt($stmt);
  }
  
  public function isRoleAssignableBy(int $role_id, UserId $user_id): bool{
    $sql = <<<SQL
SELECT 1
FROM project_roles pr
WHERE role_id = ? AND project_id = ?
  AND type = 'normal'
  AND ordering > COALESCE((SELECT 0
                           FROM users u
                           JOIN system_roles sr ON sr.id = u.role_id
                           WHERE u.id = ? AND sr.type = 'admin'),
                          (SELECT ordering
                           FROM project_roles pr2
                           JOIN project_members pm ON pr2.role_id = pm.role_id AND pr2.project_id = pm.project_id
                           WHERE pm.user_id = ? AND pm.project_id = pr.project_id),
                          ~0)
SQL;
    
    $stmt = $this->execute($sql, 'IISS', [$role_id, $this->getProjectId(), $user_id, $user_id]);
    return $this->fetchOneInt($stmt) !== null;
  }
  
  /**
   * @return RoleInfo[]
   */
  public function listRoles(): array{
    $stmt = $this->execute('SELECT role_id, type, title, ordering FROM project_roles WHERE project_id = ? ORDER BY ordering ASC',
                           'I', [$this->getProjectId()]);
    
    return $this->fetchMap($stmt, fn($v): RoleInfo => new RoleInfo($v['role_id'], $v['type'], $v['title'], (int)$v['ordering']));
  }
  
  /**
   * @param UserId $user_id
   * @return RoleInfo[]
   */
  public function listRolesAssignableBy(UserId $user_id): array{
    $sql = <<<SQL
SELECT role_id, type, title, ordering
FROM project_roles pr
WHERE project_id = ?
  AND type = 'normal'
  AND ordering > COALESCE((SELECT 0
                           FROM users u
                           JOIN system_roles sr ON sr.id = u.role_id
                           WHERE u.id = ? AND sr.type = 'admin'),
                          (SELECT ordering
                           FROM project_roles pr2
                           JOIN project_members pm ON pr2.role_id = pm.role_id AND pr2.project_id = pm.project_id
                           WHERE pm.user_id = ? AND pm.project_id = pr.project_id),
                          ~0)
ORDER BY ordering ASC
SQL;
    
    $stmt = $this->execute($sql, 'ISS', [$this->getProjectId(), $user_id, $user_id]);
    return $this->fetchMap($stmt, fn($v): RoleInfo => new RoleInfo($v['role_id'], $v['type'], $v['title'], (int)$v['ordering']));
  }
  
  public function getRoleIdByTitle(string $title): ?int{
    $stmt = $this->execute('SELECT role_id FROM project_roles WHERE title = ? AND project_id = ?',
                           'SI', [$title, $this->getProjectId()]);
    
    return $this->fetchOneColumn($stmt);
  }
  
  public function getRoleTitleIfNormal(int $id): ?string{
    $stmt = $this->execute('SELECT title FROM project_roles WHERE role_id = ? AND project_id = ? AND type = \'normal\'',
                           'II', [$id, $this->getProjectId()]);
    
    return $this->fetchOneColumn($stmt);
  }
  
  public function deleteById(int $id): void{
    $project = $this->getProjectId();
    
    $this->db->beginTransaction();
    
    try{
      $stmt = $this->execute('SELECT ordering FROM project_roles WHERE role_id = ? AND project_id = ? AND type = \'normal\'',
                             'II', [$id, $this->getProjectId()]);
      
      $ordering = $this->fetchOneInt($stmt);
      
      if ($ordering === null){
        $this->db->rollBack();
        return;
      }
      
      $this->execute('UPDATE project_members SET role_id = NULL WHERE role_id = ? AND project_id = ?',
                     'II', [$id, $project]);
      
      $this->execute('DELETE FROM project_roles WHERE role_id = ? AND project_id = ? AND type = \'normal\'',
                     'II', [$id, $project]);
      
      $this->execute('UPDATE project_roles SET ordering = ordering - 1 WHERE ordering > ? AND project_id = ? AND type = \'normal\'',
                     'II', [$ordering, $project]);
      
      $this->db->commit();
    }catch(PDOException $e){
      $this->db->rollBack();
      throw $e;
    }
  }
}

?>
