<?php
declare(strict_types = 1);

namespace Database\Tables;

use Data\UserId;
use Database\AbstractProjectTable;
use Database\Filters\AbstractFilter;
use Database\Filters\Types\ProjectMemberFilter;
use Database\Objects\ProjectMember;
use PDO;
use PDOException;

final class ProjectMemberTable extends AbstractProjectTable{
  public function addMember(UserId $user_id, ?int $role_id): void{
    $stmt = $this->db->prepare('INSERT INTO project_members (project_id, user_id, role_id) VALUES (?, ?, ?)');
    $stmt->bindValue(1, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->bindValue(2, $user_id);
    $stmt->bindValue(3, $role_id, $role_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->execute();
  }
  
  public function countMembers(?ProjectMemberFilter $filter = null): ?int{
    $filter = $this->prepareFilter($filter ?? ProjectMemberFilter::empty(), 'pm');
    
    $sql = <<<SQL
SELECT COUNT(*)
FROM project_members pm
LEFT JOIN project_roles pr ON pm.role_id = pr.role_id AND pm.project_id = pr.project_id
SQL;
    
    $stmt = $filter->prepare($this->db, $sql, AbstractFilter::STMT_COUNT);
    $stmt->execute();
    return $this->fetchOneInt($stmt);
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
LEFT JOIN project_roles pr ON pm.role_id = pr.role_id AND pm.project_id = pr.project_id
JOIN      users u ON pm.user_id = u.id
SQL;
    
    $stmt = $filter->prepare($this->db, $sql);
    $stmt->execute();
    return $this->fetchMap($stmt, fn($v): ProjectMember => new ProjectMember(UserId::fromRaw($v['user_id']), $v['name'], $v['role_id'], $v['role_title']));
  }
  
  public function setRole(UserId $user_id, ?int $role_id): void{
    $stmt = $this->db->prepare('UPDATE project_members SET role_id = ? WHERE user_id = ? AND project_id = ?');
    $stmt->bindValue(1, $role_id, $role_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue(2, $user_id);
    $stmt->bindValue(3, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->execute();
  }
  
  public function getRoleIdStr(UserId $user_id): ?string{
    $stmt = $this->db->prepare('SELECT role_id FROM project_members WHERE user_id = ? AND project_id = ?');
    $stmt->bindValue(1, $user_id);
    $stmt->bindValue(2, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->execute();
    
    $res = $this->fetchOneColumnRaw($stmt);
    return $res === false ? null : ($res === null ? '' : (string)(int)$res);
  }
  
  public function checkMembershipExists(UserId $user_id): bool{
    $stmt = $this->db->prepare('SELECT 1 FROM project_members WHERE user_id = ? AND project_id = ?');
    $stmt->bindValue(1, $user_id);
    $stmt->bindValue(2, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->execute();
    return $this->fetchOneColumn($stmt) !== null;
  }
  
  public function removeByUserId(UserId $user_id, bool $reassign_issues, ?UserId $reassign_user_id = null): void{
    $this->db->beginTransaction();
    
    try{
      if ($reassign_issues){
        $stmt = $this->db->prepare('UPDATE issues SET assignee_id = ? WHERE assignee_id = ? AND project_id = ?');
        $stmt->bindValue(1, $reassign_user_id);
        $stmt->bindValue(2, $user_id);
        $stmt->bindValue(3, $this->getProjectId(), PDO::PARAM_INT);
        $stmt->execute();
      }
      
      $stmt = $this->db->prepare('DELETE FROM project_members WHERE user_id = ? AND project_id = ?');
      $stmt->bindValue(1, $user_id);
      $stmt->bindValue(2, $this->getProjectId(), PDO::PARAM_INT);
      $stmt->execute();
      
      $this->db->commit();
    }catch(PDOException $e){
      $this->db->rollBack();
      throw $e;
    }
  }
}

?>
