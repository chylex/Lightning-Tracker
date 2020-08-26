<?php
declare(strict_types = 1);

namespace Database\Tables;

use Database\AbstractProjectTable;
use Database\Filters\AbstractFilter;
use Database\Filters\Types\ProjectMemberFilter;
use Database\Objects\ProjectInfo;
use Database\Objects\ProjectMember;
use PDO;

final class ProjectMemberTable extends AbstractProjectTable{
  public function __construct(PDO $db, ProjectInfo $project){
    parent::__construct($db, $project);
  }
  
  public function addMember(int $user_id, ?int $role_id): void{
    $stmt = $this->db->prepare('INSERT INTO project_members (project_id, user_id, role_id) VALUES (?, ?, ?)');
    $stmt->bindValue(1, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->bindValue(2, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(3, $role_id, $role_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->execute();
  }
  
  public function countMembers(?ProjectMemberFilter $filter = null): ?int{
    $filter = $this->prepareFilter($filter ?? ProjectMemberFilter::empty(), 'pm');
    
    $sql = <<<SQL
SELECT COUNT(*)
FROM project_members pm
LEFT JOIN project_roles pr ON pm.project_id = pr.project_id AND pm.role_id = pr.role_id
SQL;
    
    $stmt = $filter->prepare($this->db, $sql, AbstractFilter::STMT_COUNT);
    $stmt->execute();
    
    $count = $this->fetchOneColumn($stmt);
    return $count === false ? null : (int)$count;
  }
  
  /**
   * @param ProjectMemberFilter|null $filter
   * @return ProjectMember[]
   */
  public function listMembers(?ProjectMemberFilter $filter = null): array{
    $filter = $this->prepareFilter($filter ?? ProjectMemberFilter::empty(), 'pm');
    
    $sql = <<<SQL
SELECT pm.user_id              AS user_id,
       u.name                  AS name,
       pr.role_id              AS role_id,
       pr.title                AS role_title,
       IFNULL(pr.ordering, ~0) AS role_order
FROM project_members pm
LEFT JOIN project_roles pr ON pm.project_id = pr.project_id AND pm.role_id = pr.role_id
JOIN      users u ON pm.user_id = u.id
SQL;
    
    $stmt = $filter->prepare($this->db, $sql);
    $stmt->execute();
    
    $results = [];
    
    while(($res = $this->fetchNext($stmt)) !== false){
      $results[] = new ProjectMember($res['user_id'], $res['name'], $res['role_id'], $res['role_title']);
    }
    
    return $results;
  }
  
  public function setRole(int $user_id, ?int $role_id){
    $stmt = $this->db->prepare('UPDATE project_members SET role_id = ? WHERE user_id = ? AND project_id = ?');
    $stmt->bindValue(1, $role_id, $role_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue(2, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(3, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->execute();
  }
  
  public function getRoleIdStr(int $user_id): ?string{
    $stmt = $this->db->prepare('SELECT role_id FROM project_members WHERE user_id = ? AND project_id = ?');
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->execute();
    
    $res = $this->fetchOneColumn($stmt);
    return $res === false ? null : ($res === null ? '' : strval((int)$res));
  }
  
  public function checkMembershipExists(int $user_id): bool{
    $stmt = $this->db->prepare('SELECT 1 FROM project_members WHERE user_id = ? AND project_id = ?');
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->execute();
    return (bool)$this->fetchOneColumn($stmt);
  }
  
  public function removeUserId(int $user_id): void{
    $stmt = $this->db->prepare('DELETE FROM project_members WHERE user_id = ? AND project_id = ?');
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->execute();
  }
}

?>
