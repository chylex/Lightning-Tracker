<?php
declare(strict_types = 1);

namespace Database\Tables;

use Database\AbstractProjectTable;
use Database\Filters\AbstractFilter;
use Database\Filters\Types\IssueFilter;
use Database\Objects\IssueDetail;
use Database\Objects\IssueInfo;
use Database\Objects\IssueUser;
use Database\Objects\UserProfile;
use LogicException;
use Pages\Components\Issues\IssuePriority;
use Pages\Components\Issues\IssueScale;
use Pages\Components\Issues\IssueStatus;
use Pages\Components\Issues\IssueType;
use PDO;
use PDOException;

final class IssueTable extends AbstractProjectTable{
  public function addIssue(UserProfile $author,
                           string $title,
                           string $description,
                           IssueType $type,
                           IssuePriority $priority,
                           IssueScale $scale,
                           IssueStatus $status,
                           int $progress,
                           ?int $milestone_id,
                           ?int $assignee_id
  ): int{
    $this->db->beginTransaction();
    
    try{
      $stmt = $this->db->prepare('SELECT (1 + IFNULL(MAX(issue_id), 0)) FROM issues WHERE project_id = ?');
      $stmt->bindValue(1, $this->getProjectId(), PDO::PARAM_INT);
      $stmt->execute();
      $next_id = $this->fetchOneColumn($stmt);
      
      if ($next_id === false){
        $this->db->rollBack();
        throw new LogicException('Error calculating next issue ID.');
      }
      
      $stmt = $this->db->prepare(<<<SQL
INSERT INTO issues (project_id, issue_id, author_id, assignee_id, milestone_id, title, description, type, priority, scale, status, progress, date_created, date_updated)
VALUES (:project_id, :issue_id, :author_id, :assignee_id, :milestone_id, :title, :description, :type, :priority, :scale, :status, :progress, NOW(), NOW())
SQL
      );
      
      $stmt->bindValue('project_id', $this->getProjectId(), PDO::PARAM_INT);
      $stmt->bindValue('issue_id', $next_id, PDO::PARAM_INT);
      $stmt->bindValue('author_id', $author->getId(), PDO::PARAM_INT);
      $stmt->bindValue('assignee_id', $assignee_id, $assignee_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
      $stmt->bindValue('milestone_id', $milestone_id, $milestone_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
      $stmt->bindValue('title', $title);
      $stmt->bindValue('description', $description);
      $stmt->bindValue('type', $type->getId());
      $stmt->bindValue('priority', $priority->getId());
      $stmt->bindValue('scale', $scale->getId());
      $stmt->bindValue('status', $status->getId());
      $stmt->bindValue('progress', $progress, PDO::PARAM_INT);
      $stmt->execute();
      
      $this->db->commit();
      return $next_id;
    }catch(PDOException $e){
      $this->db->rollBack();
      throw $e;
    }
  }
  
  public function editIssue(int $id,
                            string $title,
                            string $description,
                            IssueType $type,
                            IssuePriority $priority,
                            IssueScale $scale,
                            IssueStatus $status,
                            int $progress,
                            ?int $milestone_id,
                            ?int $assignee_id
  ): void{
    $stmt = $this->db->prepare(<<<SQL
UPDATE issues
SET title = :title,
    description = :description,
    type = :type,
    priority = :priority,
    scale = :scale,
    status = :status,
    progress = :progress,
    assignee_id = :assignee_id,
    milestone_id = :milestone_id,
    date_updated = NOW()
WHERE issue_id = :issue_id AND project_id = :project_id
SQL
    );
    
    $stmt->bindValue('project_id', $this->getProjectId(), PDO::PARAM_INT);
    $stmt->bindValue('issue_id', $id, PDO::PARAM_INT);
    $stmt->bindValue('assignee_id', $assignee_id, $assignee_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue('milestone_id', $milestone_id, $milestone_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue('title', $title);
    $stmt->bindValue('description', $description);
    $stmt->bindValue('type', $type->getId());
    $stmt->bindValue('priority', $priority->getId());
    $stmt->bindValue('scale', $scale->getId());
    $stmt->bindValue('status', $status->getId());
    $stmt->bindValue('progress', $progress, PDO::PARAM_INT);
    $stmt->execute();
  }
  
  public function editIssueLimited(int $id, string $title, string $description, IssueType $type): void{
    $stmt = $this->db->prepare(<<<SQL
UPDATE issues
SET title = :title,
    description = :description,
    type = :type,
    date_updated = NOW()
WHERE issue_id = :issue_id AND project_id = :project_id
SQL
    );
    
    $stmt->bindValue('project_id', $this->getProjectId(), PDO::PARAM_INT);
    $stmt->bindValue('issue_id', $id, PDO::PARAM_INT);
    $stmt->bindValue('title', $title);
    $stmt->bindValue('description', $description);
    $stmt->bindValue('type', $type->getId());
    $stmt->execute();
  }
  
  public function updateIssueStatus(int $id, IssueStatus $status, ?int $progress = null): void{
    $stmt = $this->db->prepare(<<<SQL
UPDATE issues
SET status = ?,
    progress = IFNULL(?, progress),
    date_updated = NOW()
WHERE issue_id = ? AND project_id = ?
SQL
    );
    
    $stmt->bindValue(1, $status->getId());
    $stmt->bindValue(2, $progress, $progress === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue(3, $id, PDO::PARAM_INT);
    $stmt->bindValue(4, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->execute();
  }
  
  public function updateIssueTasks(int $id, string $description, int $progress): void{
    if ($progress === 100){
      $condition = 'status = \''.IssueStatus::OPEN.'\' OR status = \''.IssueStatus::IN_PROGRESS.'\'';
      $auto_status = IssueStatus::READY_TO_TEST;
    }
    elseif ($progress > 0){
      $condition = 'status = \''.IssueStatus::OPEN.'\'';
      $auto_status = IssueStatus::IN_PROGRESS;
    }
    else{
      $condition = 'FALSE';
      $auto_status = IssueStatus::OPEN;
    }
    
    $stmt = $this->db->prepare(<<<SQL
UPDATE issues
SET description = ?,
    progress = ?,
    status = IF($condition, '$auto_status', status),
    date_updated = NOW()
WHERE issue_id = ? AND project_id = ?
SQL
    );
    
    $stmt->bindValue(1, $description);
    $stmt->bindValue(2, $progress, PDO::PARAM_INT);
    $stmt->bindValue(3, $id, PDO::PARAM_INT);
    $stmt->bindValue(4, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->execute();
  }
  
  public function countIssues(?IssueFilter $filter = null): ?int{
    $filter = $this->prepareFilter($filter ?? IssueFilter::empty(), 'i');
    
    if ($filter->isEmpty()){
      $stmt = $filter->prepare($this->db, 'SELECT COUNT(*) FROM issues i', AbstractFilter::STMT_COUNT);
    }
    else{
      $stmt = $filter->prepare($this->db, 'SELECT COUNT(*) FROM issues i LEFT JOIN milestones m ON i.project_id = m.project_id AND i.milestone_id = m.milestone_id', AbstractFilter::STMT_COUNT);
    }
    
    $stmt->execute();
    
    $count = $this->fetchOneColumn($stmt);
    return $count === false ? null : (int)$count;
  }
  
  /**
   * @param IssueFilter|null $filter
   * @return IssueInfo[]
   */
  public function listIssues(?IssueFilter $filter = null): array{
    $filter = $this->prepareFilter($filter ?? IssueFilter::empty(), 'i');
    
    $stmt = $filter->prepare($this->db, <<<SQL
SELECT i.issue_id AS id,
       i.title,
       i.type,
       i.priority,
       i.scale,
       i.status,
       i.progress,
       i.date_created,
       i.date_updated
FROM issues i
LEFT JOIN milestones m ON i.project_id = m.project_id AND i.milestone_id = m.milestone_id
SQL
    );
    
    $stmt->execute();
    
    $results = [];
    
    while(($res = $this->fetchNext($stmt)) !== false){
      $results[] = new IssueInfo($res['id'],
                                 $res['title'],
                                 IssueType::get($res['type']),
                                 IssuePriority::get($res['priority']),
                                 IssueScale::get($res['scale']),
                                 IssueStatus::get($res['status']),
                                 $res['progress'],
                                 $res['date_created'],
                                 $res['date_updated']);
    }
    
    return $results;
  }
  
  public function getIssueDetail(int $id): ?IssueDetail{
    $stmt = $this->db->prepare(<<<SQL
SELECT issues.title        AS title,
       issues.description  AS description,
       issues.type         AS type,
       issues.priority     AS priority,
       issues.scale        AS scale,
       issues.status       AS status,
       issues.progress     AS progress,
       issues.date_created AS date_created,
       issues.date_updated AS date_updated,
       issues.author_id    AS author_id,
       author.name         AS author_name,
       issues.assignee_id  AS assignee_id,
       assignee.name       AS assignee_name,
       issues.milestone_id AS milestone_id,
       milestone.title     AS milestone_title
FROM issues
LEFT JOIN users author ON issues.author_id = author.id
LEFT JOIN users assignee ON issues.assignee_id = assignee.id
LEFT JOIN milestones milestone ON issues.project_id = milestone.project_id AND issues.milestone_id = milestone.milestone_id
WHERE issues.issue_id = :issue_id AND issues.project_id = :project_id
SQL
    );
    
    $stmt->bindValue('project_id', $this->getProjectId(), PDO::PARAM_INT);
    $stmt->bindValue('issue_id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $res = $this->fetchOne($stmt);
    
    if ($res === false){
      return null;
    }
    
    return new IssueDetail($id,
                           $res['title'],
                           $res['description'],
                           IssueType::get($res['type']),
                           IssuePriority::get($res['priority']),
                           IssueScale::get($res['scale']),
                           IssueStatus::get($res['status']),
                           $res['progress'],
                           $res['date_created'],
                           $res['date_updated'],
                           $res['milestone_id'],
                           $res['milestone_title'],
                           $res['author_id'] === null ? null : new IssueUser($res['author_id'], $res['author_name']),
                           $res['assignee_id'] === null ? null : new IssueUser($res['assignee_id'], $res['assignee_name']));
  }
  
  public function getIssueDescription(int $id): string{
    $stmt = $this->db->prepare('SELECT description FROM issues WHERE issue_id = ? AND project_id = ?');
    $stmt->bindValue(1, $id, PDO::PARAM_INT);
    $stmt->bindValue(2, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->execute();
    return $this->fetchOneColumn($stmt);
  }
  
  public function deleteById(int $id): void{
    $stmt = $this->db->prepare('DELETE FROM issues WHERE issue_id = ? AND project_id = ?');
    $stmt->bindValue(1, $id, PDO::PARAM_INT);
    $stmt->bindValue(2, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->execute();
  }
}

?>
