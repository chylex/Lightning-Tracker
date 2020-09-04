<?php
declare(strict_types = 1);

namespace Database\Tables;

use Database\AbstractProjectTable;
use Database\Filters\AbstractFilter;
use Database\Filters\Types\MilestoneFilter;
use Database\Objects\MilestoneInfo;
use LogicException;
use PDO;
use PDOException;

final class MilestoneTable extends AbstractProjectTable{
  public function addMilestone(string $title): void{
    $this->db->beginTransaction();
    
    try{
      $stmt = $this->db->prepare(<<<SQL
SELECT IFNULL(MAX(milestone_id) + 1, 1) AS id,
       IFNULL(MAX(ordering) + 1, 1)     AS ordering
FROM milestones
WHERE project_id = ?
SQL
      );
      
      $stmt->bindValue(1, $this->getProjectId(), PDO::PARAM_INT);
      $stmt->execute();
      
      $next = $this->fetchOneRaw($stmt);
      
      if ($next === false){
        $this->db->rollBack();
        throw new LogicException('Error calculating next milestone ID.');
      }
      
      $stmt = $this->db->prepare('INSERT INTO milestones (project_id, milestone_id, ordering, title) VALUES (?, ?, ?, ?)');
      $stmt->bindValue(1, $this->getProjectId(), PDO::PARAM_INT);
      $stmt->bindValue(2, $next['id'], PDO::PARAM_INT);
      $stmt->bindValue(3, $next['ordering'], PDO::PARAM_INT);
      $stmt->bindValue(4, $title);
      $stmt->execute();
      
      $this->db->commit();
    }catch(PDOException $e){
      $this->db->rollBack();
      throw $e;
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
      throw $e;
    }
  }
  
  public function moveMilestoneDown(int $id): void{
    $this->db->beginTransaction();
    
    try{
      $limit = $this->findMaxOrdering();
      
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
      throw $e;
    }
  }
  
  public function findMaxOrdering(): ?int{
    $stmt = $this->db->prepare('SELECT MAX(ordering) FROM milestones WHERE project_id = ?');
    $stmt->bindValue(1, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->execute();
    return $this->fetchOneInt($stmt);
  }
  
  private function swapMilestonesInternal(int $id, int $current_ordering, int $other_ordering): void{
    $stmt = $this->db->prepare('UPDATE milestones SET ordering = ? WHERE ordering = ? AND project_id = ?');
    $stmt->bindValue(1, $current_ordering, PDO::PARAM_INT);
    $stmt->bindValue(2, $other_ordering, PDO::PARAM_INT);
    $stmt->bindValue(3, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->execute();
    
    $stmt = $this->db->prepare('UPDATE milestones SET ordering = ? WHERE milestone_id = ? AND project_id = ?');
    $stmt->bindValue(1, $other_ordering, PDO::PARAM_INT);
    $stmt->bindValue(2, $id, PDO::PARAM_INT);
    $stmt->bindValue(3, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->execute();
  }
  
  private function getMilestoneOrdering(int $id): ?int{
    $stmt = $this->db->prepare('SELECT ordering FROM milestones WHERE milestone_id = ? AND project_id = ?');
    $stmt->bindValue(1, $id, PDO::PARAM_INT);
    $stmt->bindValue(2, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->execute();
    return $this->fetchOneInt($stmt);
  }
  
  public function countMilestones(?MilestoneFilter $filter = null): ?int{
    $filter = $this->prepareFilter($filter ?? MilestoneFilter::empty());
    
    $stmt = $filter->prepare($this->db, 'SELECT COUNT(*) FROM milestones', AbstractFilter::STMT_COUNT);
    $stmt->execute();
    return $this->fetchOneInt($stmt);
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
       m.ordering                                                       AS ordering,
       COUNT(CASE WHEN i.status IN ('finished', 'rejected') THEN 1 END) AS closed_issues,
       COUNT(i.issue_id)                                                AS total_issues,
       FLOOR(SUM(i.progress * iw.contribution) / SUM(iw.contribution))  AS progress,
       MAX(i.date_updated)                                              AS date_updated
FROM milestones m
LEFT JOIN issues i ON m.milestone_id = i.milestone_id AND m.project_id = i.project_id
LEFT JOIN issue_weights iw ON i.scale = iw.scale
# WHERE
GROUP BY m.milestone_id, m.title
# ORDER
# LIMIT
SQL;
    
    $stmt = $filter->prepare($this->db, $sql, AbstractFilter::STMT_SELECT_INJECT);
    $stmt->execute();
    return $this->fetchMap($stmt, fn($v): MilestoneInfo => new MilestoneInfo($v['milestone_id'],
                                                                             $v['title'],
                                                                             $v['ordering'],
                                                                             $v['closed_issues'],
                                                                             $v['total_issues'],
                                                                             $v['progress'] === null ? null : (int)$v['progress'],
                                                                             $v['date_updated']));
  }
  
  public function setMilestoneTitle(int $id, string $title): void{
    $stmt = $this->db->prepare('UPDATE milestones SET title = ? WHERE milestone_id = ? AND project_id = ?');
    $stmt->bindValue(1, $title);
    $stmt->bindValue(2, $id, PDO::PARAM_INT);
    $stmt->bindValue(3, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->execute();
  }
  
  public function getMilestoneTitle(int $id): ?string{
    $stmt = $this->db->prepare('SELECT title FROM milestones WHERE milestone_id = ? AND project_id = ?');
    $stmt->bindValue(1, $id, PDO::PARAM_INT);
    $stmt->bindValue(2, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->execute();
    return $this->fetchOneColumn($stmt);
  }
  
  public function deleteById(int $id, ?int $replacement_id): void{
    $project = $this->getProjectId();
    
    $this->db->beginTransaction();
    
    try{
      $ordering = $this->getMilestoneOrdering($id);
      
      if ($ordering === null){
        $this->db->rollBack();
        return;
      }
      
      $stmt = $this->db->prepare('UPDATE milestones SET ordering = ordering - 1 WHERE ordering > ? AND project_id = ?');
      $stmt->bindValue(1, $ordering, PDO::PARAM_INT);
      $stmt->bindValue(2, $project, PDO::PARAM_INT);
      $stmt->execute();
      
      foreach(['UPDATE issues SET milestone_id = ? WHERE milestone_id = ? AND project_id = ?',
               'UPDATE project_user_settings SET active_milestone = ? WHERE active_milestone = ? AND project_id = ?'] as $sql){
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $replacement_id, $replacement_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(2, $id, PDO::PARAM_INT);
        $stmt->bindValue(3, $project, PDO::PARAM_INT);
        $stmt->execute();
      }
      
      $stmt = $this->db->prepare('DELETE FROM milestones WHERE milestone_id = ? AND project_id = ?');
      $stmt->bindValue(1, $id, PDO::PARAM_INT);
      $stmt->bindValue(2, $project, PDO::PARAM_INT);
      $stmt->execute();
      
      $this->db->commit();
    }catch(PDOException $e){
      $this->db->rollBack();
      throw $e;
    }
  }
}

?>
