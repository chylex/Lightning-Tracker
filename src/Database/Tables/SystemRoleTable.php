<?php
declare(strict_types = 1);

namespace Database\Tables;

use Data\UserId;
use Database\AbstractTable;
use Database\Objects\RoleInfo;
use Exception;
use LogicException;
use PDOException;

final class SystemRoleTable extends AbstractTable{
  /**
   * @param string $title
   * @param bool $special
   * @return int
   * @throws Exception
   */
  public function addRole(string $title, bool $special = false): int{
    $this->db->beginTransaction();
    
    try{
      $sql = <<<SQL
SELECT IFNULL(MAX(id) + 1, 1)       AS id,
       IFNULL(MAX(ordering) + 1, 1) AS ordering
FROM system_roles
SQL;
      
      $stmt = $this->db->query($sql);
      $next = $this->fetchOneRaw($stmt);
      
      if ($next === false){
        $this->db->rollBack();
        throw new LogicException('Error calculating next role ID.');
      }
      
      $role_id = $next['id'];
      
      $this->execute('INSERT INTO system_roles (id, title, ordering, special) VALUES (?, ?, ?, ?)',
                     'ISIB', [$role_id, $title, $special ? 0 : $next['ordering'], $special]);
      
      $this->db->commit();
      return $role_id;
    }catch(PDOException $e){
      $this->db->rollBack();
      throw $e;
    }
  }
  
  public function editRole(int $id, string $title): void{
    $this->execute('UPDATE system_roles SET title = ? WHERE id = ?',
                   'SI', [$title, $id]);
  }
  
  public function swapRolesIfNotSpecial(int $ordering1, int $ordering2): void{
    $sql = <<<SQL
UPDATE system_roles sr1 INNER JOIN system_roles sr2 ON sr1.ordering = ? AND sr2.ordering = ?
SET sr1.ordering = sr2.ordering,
    sr2.ordering = sr1.ordering
WHERE sr1.special = FALSE AND sr2.special = FALSE
SQL;
    
    $this->execute($sql, 'II', [$ordering1, $ordering2]);
  }
  
  public function findMaxOrdering(): ?int{
    $stmt = $this->db->query('SELECT MAX(ordering) FROM system_roles');
    return $this->fetchOneInt($stmt);
  }
  
  public function isRoleAssignableBy(int $role_id, UserId $user_id): bool{
    $sql = <<<SQL
SELECT 1
FROM system_roles sr
WHERE id = ?
  AND special = FALSE
  AND ordering > IFNULL((SELECT ordering
                         FROM system_roles sr2
                         JOIN users u ON sr2.id = u.role_id
                         WHERE u.id = ?), ~0)
SQL;
    
    $stmt = $this->execute($sql, 'IS', [$role_id, $user_id]);
    return $this->fetchOneInt($stmt) !== null;
  }
  
  /**
   * @return RoleInfo[]
   */
  public function listRoles(): array{
    $stmt = $this->db->query('SELECT id, title, ordering, special FROM system_roles ORDER BY special DESC, ordering ASC');
    return $this->fetchMap($stmt, fn($v): RoleInfo => new RoleInfo($v['id'], $v['title'], $v['ordering'], (bool)$v['special']));
  }
  
  /**
   * @param UserId $user_id
   * @return RoleInfo[]
   */
  public function listRolesAssignableBy(UserId $user_id): array{
    $sql = <<<SQL
SELECT id, title, ordering, special
FROM system_roles sr
WHERE special = FALSE
  AND ((SELECT u.admin FROM users u WHERE u.id = ?) OR
       (ordering > IFNULL((SELECT ordering
                           FROM system_roles sr2
                           JOIN users u ON sr2.id = u.role_id
                           WHERE u.id = ?), ~0)))
ORDER BY ordering ASC
SQL;
    
    $stmt = $this->execute($sql, 'SS', [$user_id, $user_id]);
    return $this->fetchMap($stmt, fn($v): RoleInfo => new RoleInfo($v['id'], $v['title'], (int)$v['ordering'], (bool)$v['special']));
  }
  
  public function getRoleIdByTitle(string $title): ?int{
    $stmt = $this->execute('SELECT id FROM system_roles WHERE title = ?',
                           'S', [$title]);
    
    return $this->fetchOneColumn($stmt);
  }
  
  public function getRoleTitleIfNotSpecial(int $id): ?string{
    $stmt = $this->execute('SELECT title FROM system_roles WHERE id = ? AND special = FALSE',
                           'I', [$id]);
    
    return $this->fetchOneColumn($stmt);
  }
  
  public function deleteById(int $id): void{
    $this->db->beginTransaction();
    
    try{
      $stmt = $this->execute('SELECT ordering FROM system_roles WHERE id = ? AND special = FALSE',
                             'I', [$id]);
      
      $ordering = $this->fetchOneInt($stmt);
  
      if ($ordering === null){
        $this->db->rollBack();
        return;
      }
      
      $this->execute('UPDATE system_roles SET ordering = ordering - 1 WHERE ordering > ? AND special = FALSE',
                     'I', [$ordering]);
      
      $this->execute('DELETE FROM system_roles WHERE id = ? AND special = FALSE',
                     'I', [$id]);
      
      $this->db->commit();
    }catch(PDOException $e){
      $this->db->rollBack();
      throw $e;
    }
  }
}

?>
