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
  
  public function swapMilestones(int $ordering1, int $ordering2): void{
    $sql = <<<SQL
UPDATE milestones m1 INNER JOIN milestones m2 ON m1.ordering = ? AND m2.ordering = ? AND m1.project_id = m2.project_id
SET m1.ordering = m2.ordering,
    m2.ordering = m1.ordering
WHERE m1.project_id = ?
SQL;
    
    $this->execute($sql, 'III', [$ordering1, $ordering2, $this->getProjectId()]);
  }
  
  public function findMaxOrdering(): ?int{
    $stmt = $this->execute('SELECT MAX(ordering) FROM milestones WHERE project_id = ?',
                           'I', [$this->getProjectId()]);
    
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
      $stmt = $this->execute('SELECT ordering FROM milestones WHERE milestone_id = ? AND project_id = ?',
                             'II', [$id, $project]);
      
      $ordering = $this->fetchOneInt($stmt);
      
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
