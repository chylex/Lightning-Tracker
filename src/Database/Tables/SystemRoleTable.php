<?php
declare(strict_types = 1);

namespace Database\Tables;

use Data\UserId;
use Database\AbstractTable;
use Database\Objects\RoleInfo;
use LogicException;
use PDOException;

final class SystemRoleTable extends AbstractTable{
  /**
   * @param string $title
   * @param string $type
   * @return int
   */
  public function addRole(string $title, string $type = RoleInfo::SYSTEM_NORMAL): int{
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
      
      $this->execute('INSERT INTO system_roles (id, type, title, ordering) VALUES (?, ?, ?, ?)',
                     'ISSI', [$role_id, $type, $title, $type === RoleInfo::SYSTEM_NORMAL ? $next['ordering'] : 0]);
      
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
  
  public function swapRolesIfNormal(int $ordering1, int $ordering2): void{
    $this->db->beginTransaction();
    
    try{
      $sql = <<<SQL
UPDATE system_roles sr1 INNER JOIN system_roles sr2 ON sr1.ordering = ? AND sr2.ordering = ?
SET sr1.ordering = -sr2.ordering,
    sr2.ordering = -sr1.ordering
WHERE sr1.type = 'normal' AND sr2.type = 'normal'
SQL;
      
      $this->execute($sql, 'II', [$ordering1, $ordering2]);
      
      $sql = <<<SQL
UPDATE system_roles
SET ordering = -ordering
WHERE ordering IN (-?, -?) AND type = 'normal'
SQL;
      
      $this->execute($sql, 'II', [$ordering1, $ordering2]);
      
      $this->db->commit();
    }catch(PDOException $e){
      $this->db->rollBack();
      throw $e;
    }
  }
  
  public function findMaxOrdering(): ?int{
    $stmt = $this->db->query('SELECT MAX(ordering) FROM system_roles');
    return $this->fetchOneInt($stmt);
  }
  
  public function isRoleAssignableBy(int $role_id, UserId $user_id): bool{
    $sql = <<<SQL
SELECT 1
FROM system_roles sr
CROSS JOIN (SELECT ur.type, ur.ordering
            FROM system_roles ur
            JOIN users u ON ur.id = u.role_id
            WHERE u.id = ?) ur
WHERE sr.id = ?
  AND sr.type = 'normal'
  AND (sr.ordering > ur.ordering OR ur.type = 'admin')
SQL;
    
    $stmt = $this->execute($sql, 'SI', [$user_id, $role_id]);
    return $this->fetchOneInt($stmt) !== null;
  }
  
  /**
   * @return RoleInfo[]
   */
  public function listRoles(): array{
    $stmt = $this->db->query('SELECT id, type, title, ordering FROM system_roles ORDER BY ordering ASC');
    return $this->fetchMap($stmt, fn($v): RoleInfo => new RoleInfo($v['id'], $v['type'], $v['title'], $v['ordering']));
  }
  
  /**
   * @param UserId $user_id
   * @return RoleInfo[]
   */
  public function listRolesAssignableBy(UserId $user_id): array{
    $sql = <<<SQL
SELECT sr.id, sr.type, sr.title, sr.ordering
FROM system_roles sr
CROSS JOIN (SELECT ur.type, ur.ordering
            FROM system_roles ur
            JOIN users u ON ur.id = u.role_id
            WHERE u.id = ?) ur
WHERE sr.type = 'normal'
  AND (sr.ordering > ur.ordering OR ur.type = 'admin')
ORDER BY sr.ordering ASC
SQL;
    
    $stmt = $this->execute($sql, 'S', [$user_id]);
    return $this->fetchMap($stmt, fn($v): RoleInfo => new RoleInfo($v['id'], $v['type'], $v['title'], (int)$v['ordering']));
  }
  
  public function getRoleIdByTitle(string $title): ?int{
    $stmt = $this->execute('SELECT id FROM system_roles WHERE title = ?',
                           'S', [$title]);
    
    return $this->fetchOneColumn($stmt);
  }
  
  public function getRoleTitleIfNormal(int $id): ?string{
    $stmt = $this->execute('SELECT title FROM system_roles WHERE id = ? AND type = \'normal\'',
                           'I', [$id]);
    
    return $this->fetchOneColumn($stmt);
  }
  
  public function deleteById(int $id): void{
    $this->db->beginTransaction();
    
    try{
      $stmt = $this->execute('SELECT ordering FROM system_roles WHERE id = ? AND type = \'normal\'',
                             'I', [$id]);
      
      $ordering = $this->fetchOneInt($stmt);
      
      if ($ordering === null){
        $this->db->rollBack();
        return;
      }
      
      $this->execute('DELETE FROM system_roles WHERE id = ? AND type = \'normal\'',
                     'I', [$id]);
      
      $this->execute('UPDATE system_roles SET ordering = ordering - 1 WHERE ordering > ? AND type = \'normal\'',
                     'I', [$ordering]);
      
      $this->db->commit();
    }catch(PDOException $e){
      $this->db->rollBack();
      throw $e;
    }
  }
}

?>
