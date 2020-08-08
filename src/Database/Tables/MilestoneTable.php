<?php
declare(strict_types = 1);

namespace Database\Tables;

use Database\AbstractTrackerTable;
use Database\Filters\Types\MilestoneFilter;
use Database\Objects\MilestoneInfo;
use Database\Objects\TrackerInfo;
use LogicException;
use PDO;
use PDOException;

final class MilestoneTable extends AbstractTrackerTable{
  public function __construct(PDO $db, TrackerInfo $tracker){
    parent::__construct($db, $tracker);
  }
  
  public function addMilestone(string $title): void{
    $this->db->beginTransaction();
    
    try{
      $stmt = $this->db->prepare('SELECT IFNULL(MAX(ordering) + 1, 1) FROM milestones WHERE tracker_id = ?');
      $stmt->bindValue(1, $this->getTrackerId(), PDO::PARAM_INT);
      $stmt->execute();
      
      $order = $this->fetchOneColumn($stmt);
      
      if ($order === false){
        $this->db->rollBack();
        throw new LogicException('Error calculating milestone order.');
      }
      
      $stmt = $this->db->prepare('INSERT INTO milestones (tracker_id, ordering, title) VALUES (?, ?, ?)');
      $stmt->bindValue(1, $this->getTrackerId(), PDO::PARAM_INT);
      $stmt->bindValue(2, $order, PDO::PARAM_INT);
      $stmt->bindValue(3, $title);
      $stmt->execute();
      
      $this->db->commit();
    }catch(PDOException $e){
      $this->db->rollBack();
    }
  }
  
  public function moveMilestoneUp(int $id): void{
    $this->db->beginTransaction();
    
    try{
      $ordering = $this->getMilestoneOrdering($id);
      
      if ($ordering === null || $ordering <= 1){
        $this->db->rollBack();
        return;
      }
      
      $this->swapMilestonesInternal($id, $ordering, $ordering - 1);
      $this->db->commit();
    }catch(PDOException $e){
      $this->db->rollBack();
    }
  }
  
  public function moveMilestoneDown(int $id): void{
    $this->db->beginTransaction();
    
    try{
      $stmt = $this->db->prepare('SELECT MAX(ordering) FROM milestones WHERE tracker_id = ?');
      $stmt->bindValue(1, $this->getTrackerId(), PDO::PARAM_INT);
      $stmt->execute();
      
      $limit = $this->fetchOneColumn($stmt);
      
      if ($limit === false){
        $this->db->rollBack();
        return;
      }
      
      $ordering = $this->getMilestoneOrdering($id);
      
      if ($ordering === null || $ordering >= $limit){
        $this->db->rollBack();
        return;
      }
      
      $this->swapMilestonesInternal($id, $ordering, $ordering + 1);
      $this->db->commit();
    }catch(PDOException $e){
      $this->db->rollBack();
    }
  }
  
  private function swapMilestonesInternal(int $id, int $current_ordering, int $other_ordering): void{
    $stmt = $this->db->prepare('UPDATE milestones SET ordering = ? WHERE ordering = ? AND tracker_id = ?');
    $stmt->bindValue(1, $current_ordering, PDO::PARAM_INT);
    $stmt->bindValue(2, $other_ordering, PDO::PARAM_INT);
    $stmt->bindValue(3, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->execute();
    
    $stmt = $this->db->prepare('UPDATE milestones SET ordering = ? WHERE id = ? AND tracker_id = ?');
    $stmt->bindValue(1, $other_ordering, PDO::PARAM_INT);
    $stmt->bindValue(2, $id, PDO::PARAM_INT);
    $stmt->bindValue(3, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->execute();
  }
  
  private function getMilestoneOrdering(int $id): ?int{
    $stmt = $this->db->prepare('SELECT ordering FROM milestones WHERE id = ? AND tracker_id = ?');
    $stmt->bindValue(1, $id, PDO::PARAM_INT);
    $stmt->bindValue(2, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->execute();
    
    $res = $this->fetchOneColumn($stmt);
    return $res === false ? null : $res;
  }
  
  public function countMilestones(?MilestoneFilter $filter = null): ?int{
    $filter = $this->prepareFilter($filter ?? MilestoneFilter::empty());
    
    $stmt = $this->db->prepare('SELECT COUNT(*) FROM milestones '.$filter->generateClauses(true));
    $filter->prepareStatement($stmt);
    $stmt->execute();
    
    $count = $this->fetchOneColumn($stmt);
    return $count === false ? null : (int)$count;
  }
  
  /**
   * @param MilestoneFilter|null $filter
   * @return MilestoneInfo[]
   */
  public function listMilestones(?MilestoneFilter $filter = null): array{
    $filter = $this->prepareFilter($filter ?? MilestoneFilter::empty());
    
    $sql = <<<SQL
SELECT m.id                                                             AS id,
       m.title                                                          AS title,
       COUNT(CASE WHEN i.status IN ('finished', 'rejected') THEN 1 END) AS closed_issues,
       COUNT(i.issue_id)                                                AS total_issues,
       FLOOR(SUM(i.progress * iw.contribution) / SUM(iw.contribution))  AS percentage_done,
       MAX(i.date_updated)                                              AS date_updated
FROM milestones m
LEFT JOIN issues i ON m.id = i.milestone_id
LEFT JOIN issue_weights iw ON i.scale = iw.scale
# WHERE
GROUP BY m.id, m.title
# ORDER
# LIMIT
SQL;
    
    $stmt = $this->db->prepare($filter->injectClauses($sql, 'm'));
    $filter->prepareStatement($stmt);
    $stmt->execute();
    
    $results = [];
    
    while(($res = $this->fetchNext($stmt)) !== false){
      $results[] = new MilestoneInfo($res['id'],
                                     $res['title'],
                                     $res['closed_issues'],
                                     $res['total_issues'],
                                     $res['percentage_done'] === null ? null : (int)$res['percentage_done'],
                                     $res['date_updated']);
    }
    
    return $results;
  }
  
  public function deleteById(int $id): void{
    $this->db->beginTransaction();
    
    try{
      $ordering = $this->getMilestoneOrdering($id);
      
      if ($ordering === null){
        $this->db->rollBack();
        return;
      }
      
      $stmt = $this->db->prepare('UPDATE milestones SET ordering = ordering - 1 WHERE ordering > ? AND tracker_id = ?');
      $stmt->bindValue(1, $ordering, PDO::PARAM_INT);
      $stmt->bindValue(2, $this->getTrackerId(), PDO::PARAM_INT);
      $stmt->execute();
      
      $stmt = $this->db->prepare('DELETE FROM milestones WHERE id = ? AND tracker_id = ?');
      $stmt->bindValue(1, $id, PDO::PARAM_INT);
      $stmt->bindValue(2, $this->getTrackerId(), PDO::PARAM_INT);
      $stmt->execute();
      
      $this->db->commit();
    }catch(PDOException $e){
      $this->db->rollBack();
    }
  }
}

?>
