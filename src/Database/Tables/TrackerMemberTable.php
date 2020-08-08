<?php
declare(strict_types = 1);

namespace Database\Tables;

use Database\AbstractTrackerTable;
use Database\Filters\Types\TrackerMemberFilter;
use Database\Objects\TrackerInfo;
use Database\Objects\TrackerMember;
use PDO;

final class TrackerMemberTable extends AbstractTrackerTable{
  public function __construct(PDO $db, TrackerInfo $tracker){
    parent::__construct($db, $tracker);
  }
  
  public function setRole(int $user_id, ?int $role_id): void{
    $stmt = $this->db->prepare('INSERT INTO tracker_members (tracker_id, user_id, role_id) VALUES (?, ?, ?)');
    $stmt->bindValue(1, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->bindValue(2, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(3, $role_id, $role_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->execute();
  }
  
  public function countMembers(?TrackerMemberFilter $filter = null): ?int{
    $filter = $this->prepareFilter($filter ?? TrackerMemberFilter::empty());
    
    $stmt = $this->db->prepare('SELECT COUNT(*) FROM tracker_members '.$filter->generateClauses(true));
    $filter->prepareStatement($stmt);
    $stmt->execute();
    
    $count = $this->fetchOneColumn($stmt);
    return $count === false ? null : (int)$count;
  }
  
  /**
   * @param TrackerMemberFilter|null $filter
   * @return TrackerMember[]
   */
  public function listMembers(?TrackerMemberFilter $filter = null): array{
    $filter = $this->prepareFilter($filter ?? TrackerMemberFilter::empty());
    
    $sql = <<<SQL
SELECT user_id, u.name AS user_name, role_title, role_order
FROM (
  SELECT tm.user_id                 AS user_id,
         tr.title                   AS role_title,
         IFNULL(tm.role_id + 1, ~0) AS role_order,
         tm.tracker_id              AS tracker_id
  FROM tracker_members tm
  LEFT JOIN tracker_roles tr ON tm.role_id = tr.id
  WHERE tm.tracker_id = :tracker_id_1

  UNION

  SELECT t.owner_id AS user_id,
         'Owner'    AS role_title,
         0          AS role_order,
         t.id       AS tracker_id
  FROM trackers t
  WHERE t.id = :tracker_id_2
) sub
JOIN users u ON sub.user_id = u.id
SQL;
    
    $stmt = $this->db->prepare($sql.' '.$filter->generateClauses(false, 'sub'));
    $filter->prepareStatement($stmt);
    $stmt->bindValue('tracker_id_1', $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->bindValue('tracker_id_2', $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->execute();
    
    $results = [];
    
    while(($res = $this->fetchNext($stmt)) !== false){
      $results[] = new TrackerMember($res['user_id'], $res['user_name'], $res['role_title']);
    }
    
    return $results;
  }
  
  public function checkMembershipExists(int $user_id): bool{
    $stmt = $this->db->prepare('SELECT 1 FROM tracker_members WHERE user_id = ? AND tracker_id = ?');
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->execute();
    return (bool)$this->fetchOneColumn($stmt);
  }
  
  public function removeUserId(int $user_id): void{
    $stmt = $this->db->prepare('DELETE FROM tracker_members WHERE user_id = ? AND tracker_id = ?');
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->execute();
  }
}

?>
