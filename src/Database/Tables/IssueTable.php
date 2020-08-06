<?php
declare(strict_types = 1);

namespace Database\Tables;

use Database\AbstractTrackerTable;
use Database\Filters\Types\IssueFilter;
use Database\Objects\IssueDetail;
use Database\Objects\IssueInfo;
use Database\Objects\IssueUser;
use Database\Objects\TrackerInfo;
use Pages\Components\Issues\IssuePriority;
use Pages\Components\Issues\IssueScale;
use Pages\Components\Issues\IssueStatus;
use Pages\Components\Issues\IssueType;
use PDO;

final class IssueTable extends AbstractTrackerTable{
  public function __construct(PDO $db, TrackerInfo $tracker){
    parent::__construct($db, $tracker);
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
                           $res['milestone_title'],
                           $res['author_id'] === null ? null : new IssueUser($res['author_id'], $res['author_name']),
                           $res['assignee_id'] === null ? null : new IssueUser($res['assignee_id'], $res['assignee_name']));
  }
  
  public function deleteById(int $id): void{
    $stmt = $this->db->prepare('DELETE FROM issues WHERE issue_id = ? AND tracker_id = ?');
    $stmt->bindValue(1, $id, PDO::PARAM_INT);
    $stmt->bindValue(2, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->execute();
  }
}

?>
