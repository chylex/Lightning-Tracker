<?php
declare(strict_types = 1);

namespace Database\Tables;

use Database\AbstractTrackerTable;
use Database\Objects\RoleInfo;
use Database\Objects\TrackerInfo;
use Database\Objects\UserProfile;
use Database\Tables\Traits\PermTable;
use Exception;
use LogicException;
use PDO;
use PDOException;

final class TrackerPermTable extends AbstractTrackerTable{
  use PermTable;
  
  public function __construct(PDO $db, TrackerInfo $tracker){
    parent::__construct($db, $tracker);
  }
  
  protected function getDB(): PDO{
    return $this->db;
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
    
    if ($owned_transaction){
      $this->db->beginTransaction();
    }
    
    try{
      $stmt = $this->db->prepare('SELECT IFNULL(MAX(ordering) + 1, 1) AS ordering FROM tracker_roles WHERE tracker_id = ?');
      $stmt->bindValue(1, $this->getTrackerId(), PDO::PARAM_INT);
      $stmt->execute();
      
      $ordering = $this->fetchOneColumn($stmt);
      
      if ($ordering === false){
        $this->db->rollBack();
        throw new LogicException('Error calculating role order.');
      }
      
      $stmt = $this->db->prepare('INSERT INTO tracker_roles (tracker_id, title, ordering, special) VALUES (?, ?, ?, ?)');
      $stmt->bindValue(1, $this->getTrackerId(), PDO::PARAM_INT);
      $stmt->bindValue(2, $title);
      $stmt->bindValue(3, $ordering, PDO::PARAM_INT);
      $stmt->bindValue(4, $special, PDO::PARAM_BOOL);
      $stmt->execute();
      
      $id = $this->getLastInsertId();
      
      if ($id === null){
        if ($owned_transaction){
          $this->db->rollBack();
        }
        
        throw new Exception('Could not retrieve role ID.');
      }
      
      $this->addPermissions('INSERT INTO tracker_role_perms (role_id, permission) VALUES ()', $perms);
      
      if ($owned_transaction){
        $this->db->commit();
      }
      
      return $id;
    }catch(PDOException $e){
      if ($owned_transaction){
        $this->db->rollBack();
      }
      
      throw $e;
    }
  }
  
  public function isRoleSpecial(int $id): ?bool{
    $stmt = $this->db->prepare('SELECT special FROM tracker_roles WHERE id = ? AND tracker_id = ?');
    $stmt->bindValue(1, $id, PDO::PARAM_INT);
    $stmt->bindValue(2, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->execute();
    return (bool)$this->fetchOneColumn($stmt);
  }
  
  public function isRoleAssignableBy(int $role_id, int $user_id): bool{
    $stmt = $this->db->prepare(<<<SQL
SELECT 1
FROM tracker_roles
WHERE id = ? AND tracker_id = ?
  AND special = FALSE
  AND ordering > IFNULL((SELECT ordering
                         FROM tracker_roles tr2
                         JOIN tracker_members tm ON tr2.id = tm.role_id AND
                                                    tr2.tracker_id = tm.tracker_id
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
    $stmt = $this->db->prepare('SELECT id, title FROM tracker_roles WHERE tracker_id = ? ORDER BY special DESC, ordering ASC');
    $stmt->bindValue(1, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->execute();
    return $this->fetchRoles($stmt);
  }
  
  /**
   * @param int $user_id
   * @return RoleInfo[]
   */
  public function listRolesAssignableBy(int $user_id): array{
    $stmt = $this->db->prepare(<<<SQL
SELECT id, title
FROM tracker_roles
WHERE tracker_id = ?
  AND special = FALSE
  AND ordering > IFNULL((SELECT ordering
                         FROM tracker_roles tr2
                         JOIN tracker_members tm ON tr2.id = tm.role_id AND
                                                    tr2.tracker_id = tm.tracker_id
                         WHERE tm.user_id = ?), ~0)
ORDER BY ordering ASC
SQL
    );
    
    $stmt->bindValue(1, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->bindValue(2, $user_id, PDO::PARAM_INT);
    $stmt->execute();
    return $this->fetchRoles($stmt);
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
    return $this->fetchPerms($stmt);
  }
  
  public function deleteById(int $id): void{
    $stmt = $this->db->prepare('DELETE FROM tracker_roles WHERE id = ? AND tracker_id = ? AND special = FALSE');
    $stmt->bindValue(1, $id, PDO::PARAM_INT);
    $stmt->bindValue(2, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->execute();
  }
}

?>
