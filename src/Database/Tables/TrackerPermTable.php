<?php
declare(strict_types = 1);

namespace Database\Tables;

use Database\AbstractTrackerTable;
use Database\Objects\RoleInfo;
use Database\Objects\TrackerInfo;
use Database\Objects\UserProfile;
use Exception;
use LogicException;
use PDO;
use PDOException;

final class TrackerPermTable extends AbstractTrackerTable{
  public function __construct(PDO $db, TrackerInfo $tracker){
    parent::__construct($db, $tracker);
  }
  
  /**
   * @param string $title
   * @param array $perms
   * @param bool $special
   * @return int
   * @throws Exception
   */
  public function addRole(string $title, array $perms, bool $special = false): int{
    $owned_transaction = !$this->db->inTransaction();
    $tracker = $this->getTrackerId();
    
    if ($owned_transaction){
      $this->db->beginTransaction();
    }
    
    try{
      $stmt = $this->db->prepare(<<<SQL
SELECT IFNULL(MAX(role_id) + 1, 1)  AS id,
       IFNULL(MAX(ordering) + 1, 1) AS ordering
FROM tracker_roles
WHERE tracker_id = ?
SQL
      );
      
      $stmt->bindValue(1, $tracker, PDO::PARAM_INT);
      $stmt->execute();
      
      $next = $this->fetchOne($stmt);
      
      if ($next === false){
        $this->db->rollBack();
        throw new LogicException('Error calculating next role ID.');
      }
      
      $role_id = $next['id'];
      
      $stmt = $this->db->prepare('INSERT INTO tracker_roles (tracker_id, role_id, title, ordering, special) VALUES (?, ?, ?, ?, ?)');
      $stmt->bindValue(1, $tracker, PDO::PARAM_INT);
      $stmt->bindValue(2, $role_id, PDO::PARAM_INT);
      $stmt->bindValue(3, $title);
      $stmt->bindValue(4, $special ? 0 : $next['ordering'], PDO::PARAM_INT);
      $stmt->bindValue(5, $special, PDO::PARAM_BOOL);
      $stmt->execute();
      
      if (!empty($perms)){
        $sql = 'INSERT INTO tracker_role_perms (tracker_id, role_id, permission) VALUES ()';
        $values = implode(',', array_map(fn($ignore): string => '(?, ?, ?)', $perms));
        
        $stmt = $this->db->prepare(str_replace('()', $values, $sql));
        
        for($i = 0, $count = count($perms); $i < $count; $i++){
          $stmt->bindValue(($i * 3) + 1, $tracker, PDO::PARAM_INT);
          $stmt->bindValue(($i * 3) + 2, $role_id, PDO::PARAM_INT);
          $stmt->bindValue(($i * 3) + 3, $perms[$i]);
        }
        
        $stmt->execute();
      }
      
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
    $stmt = $this->db->prepare('SELECT MAX(ordering) FROM tracker_roles WHERE tracker_id = ?');
    $stmt->bindValue(1, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->execute();
    
    $limit = $this->fetchOneColumn($stmt);
    return $limit === false ? null : (int)$limit;
  }
  
  private function swapRolesInternal(int $id, int $current_ordering, int $other_ordering): void{
    $stmt = $this->db->prepare('UPDATE tracker_roles SET ordering = ? WHERE ordering = ? AND tracker_id = ?');
    $stmt->bindValue(1, $current_ordering, PDO::PARAM_INT);
    $stmt->bindValue(2, $other_ordering, PDO::PARAM_INT);
    $stmt->bindValue(3, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->execute();
    
    $stmt = $this->db->prepare('UPDATE tracker_roles SET ordering = ? WHERE role_id = ? AND tracker_id = ?');
    $stmt->bindValue(1, $other_ordering, PDO::PARAM_INT);
    $stmt->bindValue(2, $id, PDO::PARAM_INT);
    $stmt->bindValue(3, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->execute();
  }
  
  private function getRoleOrderingIfNotSpecial(int $id): ?int{
    $stmt = $this->db->prepare('SELECT ordering FROM tracker_roles WHERE role_id = ? AND tracker_id = ? AND special = FALSE');
    $stmt->bindValue(1, $id, PDO::PARAM_INT);
    $stmt->bindValue(2, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->execute();
    
    $res = $this->fetchOneColumn($stmt);
    return $res === false ? null : $res;
  }
  
  private function isRoleSpecialByOrdering(int $ordering): bool{
    $stmt = $this->db->prepare('SELECT special FROM tracker_roles WHERE ordering = ? AND tracker_id = ?');
    $stmt->bindValue(1, $ordering, PDO::PARAM_INT);
    $stmt->bindValue(2, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->execute();
    return (bool)$this->fetchOneColumn($stmt);
  }
  
  public function isRoleAssignableBy(int $role_id, int $user_id): bool{
    $stmt = $this->db->prepare(<<<SQL
SELECT 1
FROM tracker_roles
WHERE role_id = ? AND tracker_id = ?
  AND special = FALSE
  AND ordering > IFNULL((SELECT ordering
                         FROM tracker_roles tr2
                         JOIN tracker_members tm ON tr2.tracker_id = tm.tracker_id AND
                                                    tr2.role_id = tm.role_id
                         WHERE tm.user_id = ?), ~0)
SQL
    );
    
    $stmt->bindValue(1, $role_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->bindValue(3, $user_id, PDO::PARAM_INT);
    $stmt->execute();
    return $this->fetchOneColumn($stmt) !== false;
  }
  
  /**
   * @return RoleInfo[]
   */
  public function listRoles(): array{
    $stmt = $this->db->prepare('SELECT role_id, title, ordering, special FROM tracker_roles WHERE tracker_id = ? ORDER BY special DESC, ordering ASC');
    $stmt->bindValue(1, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->execute();
    
    $results = [];
    
    while(($res = $this->fetchNext($stmt)) !== false){
      $results[] = new RoleInfo($res['role_id'], $res['title'], (int)$res['ordering'], (bool)$res['special']);
    }
    
    return $results;
  }
  
  /**
   * @param int $user_id
   * @return RoleInfo[]
   */
  public function listRolesAssignableBy(int $user_id): array{
    $stmt = $this->db->prepare(<<<SQL
SELECT role_id, title, ordering, special
FROM tracker_roles
WHERE tracker_id = ?
  AND special = FALSE
  AND ordering > IFNULL((SELECT ordering
                         FROM tracker_roles tr2
                         JOIN tracker_members tm ON tr2.tracker_id = tm.tracker_id AND
                                                    tr2.role_id = tm.role_id
                         WHERE tm.user_id = ?), ~0)
ORDER BY ordering ASC
SQL
    );
    
    $stmt->bindValue(1, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->bindValue(2, $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $results = [];
    
    while(($res = $this->fetchNext($stmt)) !== false){
      $results[] = new RoleInfo($res['role_id'], $res['title'], (int)$res['ordering'], (bool)$res['special']);
    }
    
    return $results;
  }
  
  /**
   * @param ?UserProfile $user
   * @return string[]
   */
  public function listPerms(?UserProfile $user): array{
    if ($user === null){
      return [];
    }
    
    $stmt = $this->db->prepare(<<<SQL
SELECT trp.permission
FROM tracker_role_perms trp
JOIN tracker_members tm ON trp.role_id = tm.role_id
WHERE tm.user_id = ? AND tm.tracker_id = ?
SQL
    );
    
    $stmt->bindValue(1, $user->getId(), PDO::PARAM_INT);
    $stmt->bindValue(2, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->execute();
    
    $perms = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return $perms === false ? [] : $perms;
  }
  
  public function deleteById(int $id): void{
    $tracker = $this->getTrackerId();
    
    $this->db->beginTransaction();
    
    try{
      $ordering = $this->getRoleOrderingIfNotSpecial($id);
      
      if ($ordering === null){
        $this->db->rollBack();
        return;
      }
      
      $stmt = $this->db->prepare('UPDATE tracker_members SET role_id = NULL WHERE role_id = ? AND tracker_id = ?');
      $stmt->bindValue(1, $id, PDO::PARAM_INT);
      $stmt->bindValue(2, $tracker, PDO::PARAM_INT);
      $stmt->execute();
      
      $stmt = $this->db->prepare('UPDATE tracker_roles SET ordering = ordering - 1 WHERE ordering > ? AND tracker_id = ?');
      $stmt->bindValue(1, $ordering, PDO::PARAM_INT);
      $stmt->bindValue(2, $tracker, PDO::PARAM_INT);
      $stmt->execute();
      
      $stmt = $this->db->prepare('DELETE FROM tracker_roles WHERE role_id = ? AND tracker_id = ? AND special = FALSE');
      $stmt->bindValue(1, $id, PDO::PARAM_INT);
      $stmt->bindValue(2, $this->getTrackerId(), PDO::PARAM_INT);
      $stmt->execute();
      
      $this->db->commit();
    }catch(PDOException $e){
      $this->db->rollBack();
      throw $e;
    }
  }
}

?>
