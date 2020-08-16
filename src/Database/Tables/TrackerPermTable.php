<?php
declare(strict_types = 1);

namespace Database\Tables;

use Database\AbstractTrackerTable;
use Database\Objects\RoleInfo;
use Database\Objects\TrackerInfo;
use Database\Objects\UserProfile;
use Database\Tables\Traits\PermTable;
use Exception;
use PDO;
use PDOException;

final class TrackerPermTable extends AbstractTrackerTable{
  use PermTable;
  
  public const GUEST_PERMS = []; // TODO
  
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
      $stmt = $this->db->prepare('INSERT INTO tracker_roles (tracker_id, title, special) VALUES (?, ?, ?)');
      $stmt->bindValue(1, $this->getTrackerId(), PDO::PARAM_INT);
      $stmt->bindValue(2, $title);
      $stmt->bindValue(3, $special, PDO::PARAM_BOOL);
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
  
  /**
   * @return RoleInfo[]
   */
  public function listRoles(): array{
    $stmt = $this->db->prepare('SELECT id, title, special FROM tracker_roles WHERE tracker_id = ? ORDER BY special DESC, id ASC');
    $stmt->bindValue(1, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->execute();
    return $this->fetchRoles($stmt);
  }
  
  /**
   * @param ?UserProfile $user
   * @return string[]
   */
  public function listPerms(?UserProfile $user): array{
    if ($user === null){
      return self::GUEST_PERMS;
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
    return array_merge(self::GUEST_PERMS, $this->fetchPerms($stmt));
  }
  
  public function deleteById(int $id): void{
    $stmt = $this->db->prepare('DELETE FROM tracker_roles WHERE id = ? AND tracker_id = ? AND special = FALSE');
    $stmt->bindValue(1, $id, PDO::PARAM_INT);
    $stmt->bindValue(2, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->execute();
  }
}

?>
