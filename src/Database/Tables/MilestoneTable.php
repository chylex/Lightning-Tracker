<?php
declare(strict_types = 1);

namespace Database\Tables;

use Database\AbstractTrackerTable;
use Database\Filters\AbstractFilter;
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
      $stmt = $this->db->prepare(<<<SQL
SELECT IFNULL(MAX(milestone_id) + 1, 1) AS id,
       IFNULL(MAX(ordering) + 1, 1)     AS ordering
FROM milestones
WHERE tracker_id = ?
SQL
      );
      
      $stmt->bindValue(1, $this->getTrackerId(), PDO::PARAM_INT);
      $stmt->execute();
      
      $next = $this->fetchOne($stmt);
      
      if ($next === false){
        $this->db->rollBack();
        throw new LogicException('Error calculating next milestone ID.');
      }
      
      $stmt = $this->db->prepare('INSERT INTO milestones (milestone_id, tracker_id, ordering, title) VALUES (?, ?, ?, ?)');
      $stmt->bindValue(1, $next['id'], PDO::PARAM_INT);
      $stmt->bindValue(2, $this->getTrackerId(), PDO::PARAM_INT);
      $stmt->bindValue(3, $next['ordering'], PDO::PARAM_INT);
      $stmt->bindValue(4, $title);
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
    
    $stmt = $this->db->prepare('UPDATE milestones SET ordering = ? WHERE milestone_id = ? AND tracker_id = ?');
    $stmt->bindValue(1, $other_ordering, PDO::PARAM_INT);
    $stmt->bindValue(2, $id, PDO::PARAM_INT);
    $stmt->bindValue(3, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->execute();
  }
  
  private function getMilestoneOrdering(int $id): ?int{
    $stmt = $this->db->prepare('SELECT ordering FROM milestones WHERE milestone_id = ? AND tracker_id = ?');
    $stmt->bindValue(1, $id, PDO::PARAM_INT);
    $stmt->bindValue(2, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->execute();
    
    $res = $this->fetchOneColumn($stmt);
    return $res === false ? null : $res;
  }
  
  public function countMilestones(?MilestoneFilter $filter = null): ?int{
    $filter = $this->prepareFilter($filter ?? MilestoneFilter::empty());
    
    $stmt = $filter->prepare($this->db, 'SELECT COUNT(*) FROM milestones', AbstractFilter::STMT_COUNT);
    $stmt->execute();
    
    $count = $this->fetchOneColumn($stmt);
    return $count === false ? null : (int)$count;
  }
  
  /**
   * @param MilestoneFilter|null $filter
   * @return MilestoneInfo[]
   */
  public function listMilestones(?MilestoneFilter $filter = null): array{
    $filter = $this->prepareFilter($filter ?? MilestoneFilter::empty(), 'm');
    
    $sql = <<<SQL
SELECT m.milestone_id                                                   AS milestone_id,
       m.title                                                          AS title,
       COUNT(CASE WHEN i.status IN ('finished', 'rejected') THEN 1 END) AS closed_issues,
       COUNT(i.issue_id)                                                AS total_issues,
       FLOOR(SUM(i.progress * iw.contribution) / SUM(iw.contribution))  AS progress,
       MAX(i.date_updated)                                              AS date_updated
FROM milestones m
LEFT JOIN issues i ON m.tracker_id = i.tracker_id AND m.milestone_id = i.milestone_id
LEFT JOIN issue_weights iw ON i.scale = iw.scale
# WHERE
GROUP BY m.milestone_id, m.title
# ORDER
# LIMIT
SQL;
    
    $stmt = $filter->prepare($this->db, $sql, AbstractFilter::STMT_SELECT_INJECT);
    $stmt->execute();
    
    $results = [];
    
    while(($res = $this->fetchNext($stmt)) !== false){
      $results[] = new MilestoneInfo($res['milestone_id'],
                                     $res['title'],
                                     $res['closed_issues'],
                                     $res['total_issues'],
                                     $res['progress'] === null ? null : (int)$res['progress'],
                                     $res['date_updated']);
    }
    
    return $results;
  }
  
  public function setMilestoneTitle(int $id, string $title): void{
    $stmt = $this->db->prepare('UPDATE milestones SET title = ? WHERE milestone_id = ? AND tracker_id = ?');
    $stmt->bindValue(1, $title);
    $stmt->bindValue(2, $id, PDO::PARAM_INT);
    $stmt->bindValue(3, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->execute();
  }
  
  public function getMilestoneTitle(int $id): ?string{
    $stmt = $this->db->prepare('SELECT title FROM milestones WHERE milestone_id = ? AND tracker_id = ?');
    $stmt->bindValue(1, $id, PDO::PARAM_INT);
    $stmt->bindValue(2, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->execute();
    
    $title = $this->fetchOneColumn($stmt);
    return $title === false ? null : $title;
  }
  
  public function deleteById(int $id, ?int $replacement_id): void{
    $tracker = $this->getTrackerId();
    
    $this->db->beginTransaction();
    
    try{
      $ordering = $this->getMilestoneOrdering($id);
      
      if ($ordering === null){
        $this->db->rollBack();
        return;
      }
      
      $stmt = $this->db->prepare('UPDATE milestones SET ordering = ordering - 1 WHERE ordering > ? AND tracker_id = ?');
      $stmt->bindValue(1, $ordering, PDO::PARAM_INT);
      $stmt->bindValue(2, $tracker, PDO::PARAM_INT);
      $stmt->execute();
      
      foreach(['UPDATE issues SET milestone_id = ? WHERE milestone_id = ? AND tracker_id = ?',
               'UPDATE tracker_user_settings SET active_milestone = ? WHERE active_milestone = ? AND tracker_id = ?'] as $sql){
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $replacement_id, $replacement_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(2, $id, PDO::PARAM_INT);
        $stmt->bindValue(3, $tracker, PDO::PARAM_INT);
        $stmt->execute();
      }
      
      $stmt = $this->db->prepare('DELETE FROM milestones WHERE milestone_id = ? AND tracker_id = ?');
      $stmt->bindValue(1, $id, PDO::PARAM_INT);
      $stmt->bindValue(2, $tracker, PDO::PARAM_INT);
      $stmt->execute();
      
      $this->db->commit();
    }catch(PDOException $e){
      $this->db->rollBack();
      throw $e;
    }
  }
}

?>
