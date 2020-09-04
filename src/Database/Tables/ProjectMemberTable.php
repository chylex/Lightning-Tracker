<?php
declare(strict_types = 1);

namespace Database\Tables;

use Data\UserId;
use Database\AbstractProjectTable;
use Database\Filters\AbstractFilter;
use Database\Filters\Types\ProjectMemberFilter;
use Database\Objects\ProjectMember;
use PDOException;

final class ProjectMemberTable extends AbstractProjectTable{
  public function addMember(UserId $user_id, ?int $role_id): void{
    $this->execute('INSERT INTO project_members (project_id, user_id, role_id) VALUES (?, ?, ?)',
                   'ISI', [$this->getProjectId(), $user_id, $role_id]);
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
    $this->execute('UPDATE project_members SET role_id = ? WHERE user_id = ? AND project_id = ?',
                   'ISI', [$role_id, $user_id, $this->getProjectId()]);
  }
  
  public function getRoleIdStr(UserId $user_id): ?string{
    $stmt = $this->execute('SELECT role_id FROM project_members WHERE user_id = ? AND project_id = ?',
                           'SI', [$user_id, $this->getProjectId()]);
    
    $res = $this->fetchOneColumnRaw($stmt);
    return $res === false ? null : ($res === null ? '' : (string)(int)$res);
  }
  
  public function checkMembershipExists(UserId $user_id): bool{
    $stmt = $this->execute('SELECT 1 FROM project_members WHERE user_id = ? AND project_id = ?',
                           'SI', [$user_id, $this->getProjectId()]);
    
    return $this->fetchOneColumn($stmt) !== null;
  }
  
  public function removeByUserId(UserId $user_id, bool $reassign_issues, ?UserId $reassign_user_id = null): void{
    $this->db->beginTransaction();
    
    try{
      if ($reassign_issues){
        $this->execute('UPDATE issues SET assignee_id = ? WHERE assignee_id = ? AND project_id = ?',
                       'SSI', [$reassign_user_id, $user_id, $this->getProjectId()]);
      }
      
      $this->execute('DELETE FROM project_members WHERE user_id = ? AND project_id = ?',
                     'SI', [$user_id, $this->getProjectId()]);
      
      $this->db->commit();
    }catch(PDOException $e){
      $this->db->rollBack();
      throw $e;
    }
  }
}

?>
