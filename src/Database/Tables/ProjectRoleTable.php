<?php
declare(strict_types = 1);

namespace Database\Tables;

use Data\UserId;
use Database\AbstractProjectTable;
use Database\Objects\RoleInfo;
use Exception;
use LogicException;
use PDOException;

final class ProjectRoleTable extends AbstractProjectTable{
  /**
   * @param string $title
   * @param bool $special
   * @return int
   * @throws Exception
   */
  public function addRole(string $title, bool $special = false): int{
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
      
      $this->execute('INSERT INTO project_roles (project_id, role_id, title, ordering, special) VALUES (?, ?, ?, ?, ?)',
                     'IISIB', [$project, $role_id, $title, $special ? 0 : $next['ordering'], $special]);
      
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
  
  public function swapRolesIfNotSpecial(int $ordering1, int $ordering2): void{
    $sql = <<<SQL
UPDATE project_roles pr1 INNER JOIN project_roles pr2 ON pr1.ordering = ? AND pr2.ordering = ? AND pr1.project_id = pr2.project_id
SET pr1.ordering = pr2.ordering,
    pr2.ordering = pr1.ordering
WHERE pr1.project_id = ? AND pr1.special = FALSE AND pr2.special = FALSE
SQL;
    
    $this->execute($sql, 'III', [$ordering1, $ordering2, $this->getProjectId()]);
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
  AND special = FALSE
  AND ordering > IFNULL((SELECT ordering
                         FROM project_roles pr2
                         JOIN project_members pm ON pr2.role_id = pm.role_id AND pr2.project_id = pm.project_id
                         WHERE pm.user_id = ? AND pm.project_id = pr.project_id), ~0)
SQL;
    
    $stmt = $this->execute($sql, 'IIS', [$role_id, $this->getProjectId(), $user_id]);
    return $this->fetchOneInt($stmt) !== null;
  }
  
  /**
   * @return RoleInfo[]
   */
  public function listRoles(): array{
    $stmt = $this->execute('SELECT role_id, title, ordering, special FROM project_roles WHERE project_id = ? ORDER BY special DESC, ordering ASC',
                           'I', [$this->getProjectId()]);
    
    return $this->fetchMap($stmt, fn($v): RoleInfo => new RoleInfo($v['role_id'], $v['title'], (int)$v['ordering'], (bool)$v['special']));
  }
  
  /**
   * @param UserId $user_id
   * @return RoleInfo[]
   */
  public function listRolesAssignableBy(UserId $user_id): array{
    $sql = <<<SQL
SELECT role_id, title, ordering, special
FROM project_roles pr
WHERE project_id = ?
  AND special = FALSE
  AND ordering > IFNULL((SELECT ordering
                         FROM project_roles pr2
                         JOIN project_members pm ON pr2.role_id = pm.role_id AND pr2.project_id = pm.project_id
                         WHERE pm.user_id = ? AND pm.project_id = pr.project_id), ~0)
ORDER BY ordering ASC
SQL;
    
    $stmt = $this->execute($sql, 'IS', [$this->getProjectId(), $user_id]);
    return $this->fetchMap($stmt, fn($v): RoleInfo => new RoleInfo($v['role_id'], $v['title'], (int)$v['ordering'], (bool)$v['special']));
  }
  
  public function getRoleIdByTitle(string $title): ?int{
    $stmt = $this->execute('SELECT role_id FROM project_roles WHERE title = ? AND project_id = ?',
                           'SI', [$title, $this->getProjectId()]);
    
    return $this->fetchOneColumn($stmt);
  }
  
  public function getRoleTitleIfNotSpecial(int $id): ?string{
    $stmt = $this->execute('SELECT title FROM project_roles WHERE role_id = ? AND project_id = ? AND special = FALSE',
                           'II', [$id, $this->getProjectId()]);
    
    return $this->fetchOneColumn($stmt);
  }
  
  public function deleteById(int $id): void{
    $project = $this->getProjectId();
    
    $this->db->beginTransaction();
    
    try{
      $stmt = $this->execute('SELECT ordering FROM project_roles WHERE role_id = ? AND project_id = ? AND special = FALSE',
                             'II', [$id, $this->getProjectId()]);
      
      $ordering = $this->fetchOneInt($stmt);
      
      if ($ordering === null){
        $this->db->rollBack();
        return;
      }
      
      $this->execute('UPDATE project_members SET role_id = NULL WHERE role_id = ? AND project_id = ?',
                     'II', [$id, $project]);
      
      $this->execute('UPDATE project_roles SET ordering = ordering - 1 WHERE ordering > ? AND project_id = ? AND special = FALSE',
                     'II', [$ordering, $project]);
      
      $this->execute('DELETE FROM project_roles WHERE role_id = ? AND project_id = ? AND special = FALSE',
                     'II', [$id, $project]);
      
      $this->db->commit();
    }catch(PDOException $e){
      $this->db->rollBack();
      throw $e;
    }
  }
}

?>
