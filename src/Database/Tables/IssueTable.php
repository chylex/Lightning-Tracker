<?php
declare(strict_types = 1);

namespace Database\Tables;

use Data\IssuePriority;
use Data\IssueScale;
use Data\IssueStatus;
use Data\IssueType;
use Data\UserId;
use Database\AbstractProjectTable;
use Database\Filters\AbstractFilter;
use Database\Filters\Types\IssueFilter;
use Database\Objects\IssueDetail;
use Database\Objects\IssueInfo;
use Database\Objects\IssueUser;
use Database\Objects\UserProfile;
use LogicException;
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
                           ?UserId $milestone_id,
                           ?UserId $assignee_id
  ): int{
    $this->db->beginTransaction();
    
    try{
      $stmt = $this->execute('SELECT (1 + IFNULL(MAX(issue_id), 0)) FROM issues WHERE project_id = ?',
                             'I', [$this->getProjectId()]);
      
      $next_id = $this->fetchOneColumn($stmt);
      
      if ($next_id === null){
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
      $stmt->bindValue('author_id', $author->getId());
      $stmt->bindValue('assignee_id', $assignee_id);
      $stmt->bindValue('milestone_id', $milestone_id);
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
                            ?UserId $milestone_id,
                            ?UserId $assignee_id
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
    $stmt->bindValue('assignee_id', $assignee_id);
    $stmt->bindValue('milestone_id', $milestone_id);
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
    $sql = <<<SQL
UPDATE issues
SET status = ?,
    progress = IFNULL(?, progress),
    date_updated = NOW()
WHERE issue_id = ? AND project_id = ?
SQL;
    
    $this->execute($sql, 'SIII', [$status->getId(), $progress, $id, $this->getProjectId()]);
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
    
    $sql = <<<SQL
UPDATE issues
SET description = ?,
    progress = ?,
    status = IF($condition, '$auto_status', status),
    date_updated = NOW()
WHERE issue_id = ? AND project_id = ?
SQL;
    
    $this->execute($sql, 'SIII', [$description, $progress, $id, $this->getProjectId()]);
  }
  
  public function countIssues(?IssueFilter $filter = null): ?int{
    $filter = $this->prepareFilter($filter ?? IssueFilter::empty(), 'i');
    
    if ($filter->isEmpty()){
      $stmt = $filter->prepare($this->db, 'SELECT COUNT(*) FROM issues i', AbstractFilter::STMT_COUNT);
    }
    else{
      $stmt = $filter->prepare($this->db, 'SELECT COUNT(*) FROM issues i LEFT JOIN milestones m ON i.milestone_id = m.milestone_id AND i.project_id = m.project_id', AbstractFilter::STMT_COUNT);
    }
    
    $stmt->execute();
    return $this->fetchOneInt($stmt);
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
LEFT JOIN milestones m ON i.milestone_id = m.milestone_id AND i.project_id = m.project_id
SQL
    );
    
    $stmt->execute();
    return $this->fetchMap($stmt, fn($v): IssueInfo => new IssueInfo($v['id'],
                                                                     $v['title'],
                                                                     IssueType::get($v['type']),
                                                                     IssuePriority::get($v['priority']),
                                                                     IssueScale::get($v['scale']),
                                                                     IssueStatus::get($v['status']),
                                                                     $v['progress'],
                                                                     $v['date_created'],
                                                                     $v['date_updated']));
  }
  
  public function getIssueDetail(int $id): ?IssueDetail{
    $sql = <<<SQL
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
LEFT JOIN milestones milestone ON issues.milestone_id = milestone.milestone_id AND issues.project_id = milestone.project_id
WHERE issues.issue_id = ? AND issues.project_id = ?
SQL;
    
    $stmt = $this->execute($sql, 'II', [$id, $this->getProjectId()]);
    $res = $this->fetchOneRaw($stmt);
    
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
                           $res['author_id'] === null ? null : new IssueUser(UserId::fromRaw($res['author_id']), $res['author_name']),
                           $res['assignee_id'] === null ? null : new IssueUser(UserId::fromRaw($res['assignee_id']), $res['assignee_name']));
  }
  
  public function getIssueDescription(int $id): ?string{
    $stmt = $this->execute('SELECT description FROM issues WHERE issue_id = ? AND project_id = ?',
                           'II', [$id, $this->getProjectId()]);
    
    return $this->fetchOneColumn($stmt);
  }
  
  /**
   * @return IssueUser[]
   */
  public function listAuthors(): array{
    $sql = <<<SQL
SELECT u.id AS id, u.name AS name
FROM issues i
JOIN users u ON u.id = i.author_id
WHERE project_id = ?
GROUP BY u.id, u.name
ORDER BY u.name
SQL;
    
    $stmt = $this->execute($sql, 'I', [$this->getProjectId()]);
    return $this->fetchMap($stmt, fn($v): IssueUser => new IssueUser(UserId::fromRaw($v['id']), $v['name']));
  }
  
  /**
   * @return IssueUser[]
   */
  public function listAssignees(): array{
    $sql = <<<SQL
SELECT u.id AS id, u.name AS name
FROM issues i
JOIN users u ON u.id = i.assignee_id
WHERE project_id = ?
GROUP BY u.id, u.name
ORDER BY u.name
SQL;
    
    $stmt = $this->execute($sql, 'I', [$this->getProjectId()]);
    return $this->fetchMap($stmt, fn($v): IssueUser => new IssueUser(UserId::fromRaw($v['id']), $v['name']));
  }
  
  public function deleteById(int $id): void{
    $this->execute('DELETE FROM issues WHERE issue_id = ? AND project_id = ?',
                   'II', [$id, $this->getProjectId()]);
  }
}

?>
