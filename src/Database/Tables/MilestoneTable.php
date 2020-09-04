<?php
declare(strict_types = 1);

namespace Database\Tables;

use Database\AbstractProjectTable;
use Database\Filters\AbstractFilter;
use Database\Filters\Types\MilestoneFilter;
use Database\Objects\MilestoneInfo;
use LogicException;
use PDOException;

final class MilestoneTable extends AbstractProjectTable{
  public function addMilestone(string $title): void{
    $this->db->beginTransaction();
    
    try{
      $sql = <<<SQL
SELECT IFNULL(MAX(milestone_id) + 1, 1) AS id,
       IFNULL(MAX(ordering) + 1, 1)     AS ordering
FROM milestones
WHERE project_id = ?
SQL;
      
      $stmt = $this->execute($sql, 'I', [$this->getProjectId()]);
      $next = $this->fetchOneRaw($stmt);
      
      if ($next === false){
        $this->db->rollBack();
        throw new LogicException('Error calculating next milestone ID.');
      }
      
      $this->execute('INSERT INTO milestones (project_id, milestone_id, ordering, title) VALUES (?, ?, ?, ?)',
                     'IIIS', [$this->getProjectId(), $next['id'], $next['ordering'], $title]);
      
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
    $stmt = $this->execute('SELECT MAX(ordering) FROM milestones WHERE project_id = ?',
                           'I', [$this->getProjectId()]);
    
    return $this->fetchOneInt($stmt);
  }
  
  private function swapMilestonesInternal(int $id, int $current_ordering, int $other_ordering): void{
    $this->execute('UPDATE milestones SET ordering = ? WHERE ordering = ? AND project_id = ?',
                   'III', [$current_ordering, $other_ordering, $this->getProjectId()]);
    
    $this->execute('UPDATE milestones SET ordering = ? WHERE milestone_id = ? AND project_id = ?',
                   'III', [$other_ordering, $id, $this->getProjectId()]);
  }
  
  private function getMilestoneOrdering(int $id): ?int{
    $stmt = $this->execute('SELECT ordering FROM milestones WHERE milestone_id = ? AND project_id = ?',
                           'II', [$id, $this->getProjectId()]);
    
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
    $this->execute('UPDATE milestones SET title = ? WHERE milestone_id = ? AND project_id = ?',
                   'SII', [$title, $id, $this->getProjectId()]);
  }
  
  public function getMilestoneTitle(int $id): ?string{
    $stmt = $this->execute('SELECT title FROM milestones WHERE milestone_id = ? AND project_id = ?',
                           'II', [$id, $this->getProjectId()]);
    
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
      
      $this->execute('UPDATE milestones SET ordering = ordering - 1 WHERE ordering > ? AND project_id = ?',
                     'II', [$ordering, $project]);
      
      foreach(['UPDATE issues SET milestone_id = ? WHERE milestone_id = ? AND project_id = ?',
               'UPDATE project_user_settings SET active_milestone = ? WHERE active_milestone = ? AND project_id = ?'] as $sql){
        $this->execute($sql, 'III', [$replacement_id, $id, $project]);
      }
      
      $this->execute('DELETE FROM milestones WHERE milestone_id = ? AND project_id = ?',
                     'II', [$id, $project]);
      
      $this->db->commit();
    }catch(PDOException $e){
      $this->db->rollBack();
      throw $e;
    }
  }
}

?>
