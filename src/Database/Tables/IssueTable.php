<?php
declare(strict_types = 1);

namespace Database\Tables;

use Database\AbstractTrackerTable;
use Database\Filters\Types\IssueFilter;
use Database\Objects\IssueDetail;
use Database\Objects\IssueInfo;
use Database\Objects\IssueUser;
use Database\Objects\TrackerInfo;
use Database\Objects\UserProfile;
use LogicException;
use Pages\Components\Issues\IssuePriority;
use Pages\Components\Issues\IssueScale;
use Pages\Components\Issues\IssueStatus;
use Pages\Components\Issues\IssueType;
use PDO;
use PDOException;

final class IssueTable extends AbstractTrackerTable{
  public function __construct(PDO $db, TrackerInfo $tracker){
    parent::__construct($db, $tracker);
  }
  
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
      $stmt = $this->db->prepare('SELECT (1 + IFNULL(MAX(issue_id), 0)) FROM issues WHERE tracker_id = ?');
      $stmt->bindValue(1, $this->getTrackerId(), PDO::PARAM_INT);
      $stmt->execute();
      $next_id = $this->fetchOneColumn($stmt);
      
      if ($next_id === false){
        $this->db->rollBack();
        throw new LogicException('Error calculating next issue ID.');
      }
      
      $stmt = $this->db->prepare(<<<SQL
  INSERT INTO issues (tracker_id, issue_id, author_id, assignee_id, milestone_id, title, description, type, priority, scale, status, progress, date_created, date_updated)
  VALUES (:tracker_id, :issue_id, :author_id, :assignee_id, :milestone_id, :title, :description, :type, :priority, :scale, :status, :progress, NOW(), NOW())
  SQL
      );
      
      $stmt->bindValue('tracker_id', $this->getTrackerId(), PDO::PARAM_INT);
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
WHERE issue_id = :issue_id AND tracker_id = :tracker_id
SQL
    );
    
    $stmt->bindValue('tracker_id', $this->getTrackerId(), PDO::PARAM_INT);
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
  
  public function updateIssueTasks(int $id, string $description, int $progress): void{
    if ($progress === 100){
      $status_from = 'in-progress';
      $status_to = 'ready-to-test';
    }
    else{
      $status_from = 'open';
      $status_to = 'in-progress';
    }
    
    $stmt = $this->db->prepare(<<<SQL
UPDATE issues
SET description = ?, progress = ?, status = IF(status = '$status_from', '$status_to', status)
WHERE issue_id = ? AND tracker_id = ?
SQL
    );
    
    $stmt->bindValue(1, $description);
    $stmt->bindValue(2, $progress, PDO::PARAM_INT);
    $stmt->bindValue(3, $id, PDO::PARAM_INT);
    $stmt->bindValue(4, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->execute();
  }
  
  public function countIssues(?IssueFilter $filter = null): ?int{
    $filter = $this->prepareFilter($filter ?? IssueFilter::empty());
    
    $stmt = $this->db->prepare('SELECT COUNT(*) FROM issues '.$filter->generateClauses(true));
    $filter->prepareStatement($stmt);
    $stmt->execute();
    
    $count = $this->fetchOneColumn($stmt);
    return $count === false ? null : (int)$count;
  }
  
  /**
   * @param IssueFilter|null $filter
   * @return IssueInfo[]
   */
  public function listIssues(?IssueFilter $filter = null): array{
    $filter = $this->prepareFilter($filter ?? IssueFilter::empty());
    
    $stmt = $this->db->prepare('SELECT issue_id, title, type, priority, scale, status, progress, date_created, date_updated FROM issues '.$filter->generateClauses());
    $filter->prepareStatement($stmt);
    $stmt->execute();
    
    $results = [];
    
    while(($res = $this->fetchNext($stmt)) !== false){
      $results[] = new IssueInfo($res['issue_id'],
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
       milestone.id        AS milestone_id,
       milestone.title     AS milestone_title
FROM issues
LEFT JOIN users author ON issues.author_id = author.id
LEFT JOIN users assignee ON issues.assignee_id = assignee.id
LEFT JOIN milestones milestone ON issues.milestone_id = milestone.id
WHERE issues.tracker_id = :tracker_id AND issues.issue_id = :issue_id
SQL
    );
    
    $stmt->bindValue('tracker_id', $this->getTrackerId(), PDO::PARAM_INT);
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
    $stmt = $this->db->prepare('SELECT description FROM issues WHERE issue_id = ? AND tracker_id = ?');
    $stmt->bindValue(1, $id, PDO::PARAM_INT);
    $stmt->bindValue(2, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->execute();
    return $this->fetchOneColumn($stmt);
  }
  
  public function deleteById(int $id): void{
    $stmt = $this->db->prepare('DELETE FROM issues WHERE issue_id = ? AND tracker_id = ?');
    $stmt->bindValue(1, $id, PDO::PARAM_INT);
    $stmt->bindValue(2, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->execute();
  }
}

?>
