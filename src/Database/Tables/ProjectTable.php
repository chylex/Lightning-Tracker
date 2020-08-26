<?php
declare(strict_types = 1);

namespace Database\Tables;

use Database\AbstractTable;
use Database\Filters\AbstractFilter;
use Database\Filters\Types\ProjectFilter;
use Database\Objects\ProjectInfo;
use Database\Objects\ProjectVisibilityInfo;
use Database\Objects\UserProfile;
use Exception;
use PDO;
use PDOException;
use Session\Permissions\ProjectPermissions;

final class ProjectTable extends AbstractTable{
  public function __construct(PDO $db){
    parent::__construct($db);
  }
  
  /**
   * @param string $name
   * @param string $url
   * @param bool $hidden
   * @param UserProfile $owner
   * @throws Exception
   */
  public function addProject(string $name, string $url, bool $hidden, UserProfile $owner): void{
    $this->db->beginTransaction();
    
    try{
      $stmt = $this->db->prepare('INSERT INTO projects (name, url, hidden, owner_id) VALUES (:name, :url, :hidden, :owner_id)');
      
      $stmt->bindValue('name', $name);
      $stmt->bindValue('url', $url);
      $stmt->bindValue('hidden', $hidden, PDO::PARAM_BOOL);
      $stmt->bindValue('owner_id', $owner->getId(), PDO::PARAM_INT);
      $stmt->execute();
      
      $id = $this->getLastInsertId();
      
      if ($id === false){
        $this->db->rollBack();
        throw new Exception('Could not retrieve project ID.');
      }
      
      $project = new ProjectInfo($id, $name, $url, $owner->getId());
      $perms = new ProjectPermTable($this->db, $project);
      $members = new ProjectMemberTable($this->db, $project);
      
      $perms_reporter = [
          ProjectPermissions::CREATE_ISSUE
      ];
      
      $perms_developer = array_merge($perms_reporter, [
          ProjectPermissions::MODIFY_ALL_ISSUE_FIELDS,
          ProjectPermissions::EDIT_ALL_ISSUES,
          ProjectPermissions::MANAGE_MILESTONES,
          ProjectPermissions::LIST_MEMBERS
      ]);
      
      $perms_moderator = array_merge($perms_developer, [
          ProjectPermissions::DELETE_ALL_ISSUES,
          ProjectPermissions::MANAGE_MEMBERS
      ]);
      
      $perms_admin = array_merge($perms_moderator, [
          ProjectPermissions::MANAGE_SETTINGS
      ]);
      
      $owner_role_id = $perms->addRole('Owner', [], true);
      $perms->addRole('Administrator', $perms_admin);
      $perms->addRole('Moderator', $perms_moderator);
      $perms->addRole('Developer', $perms_developer);
      $perms->addRole('Reporter', $perms_reporter);
      
      $members->addMember($owner->getId(), $owner_role_id);
      
      $this->db->commit();
    }catch(PDOException $e){
      $this->db->rollBack();
      throw $e;
    }
  }
  
  public function changeSettings(int $id, string $name, bool $hidden): void{
    $stmt = $this->db->prepare('UPDATE projects SET name = ?, hidden = ? WHERE id = ?');
    $stmt->bindValue(1, $name);
    $stmt->bindValue(2, $hidden, PDO::PARAM_BOOL);
    $stmt->bindValue(3, $id, PDO::PARAM_INT);
    $stmt->execute();
  }
  
  public function countProjects(?ProjectFilter $filter = null): ?int{
    $filter ??= ProjectFilter::empty();
    
    $stmt = $filter->prepare($this->db, 'SELECT COUNT(*) FROM projects', AbstractFilter::STMT_COUNT);
    $stmt->execute();
    
    $count = $this->fetchOneColumn($stmt);
    return $count === false ? null : (int)$count;
  }
  
  /**
   * @param ProjectFilter|null $filter
   * @return ProjectInfo[]
   */
  public function listProjects(?ProjectFilter $filter = null): array{
    $filter ??= ProjectFilter::empty();
    
    $stmt = $filter->prepare($this->db, 'SELECT id, name, url, owner_id FROM projects');
    $stmt->execute();
    
    $results = [];
    
    while(($res = $this->fetchNext($stmt)) !== false){
      $results[] = new ProjectInfo($res['id'], $res['name'], $res['url'], $res['owner_id']);
    }
    
    return $results;
  }
  
  public function listProjectsOwnedBy(int $user_id): array{
    $stmt = $this->db->prepare('SELECT id, name, url FROM projects WHERE owner_id = ?');
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $results = [];
    
    while(($res = $this->fetchNext($stmt)) !== false){
      $results[] = new ProjectInfo($res['id'], $res['name'], $res['url'], $user_id);
    }
    
    return $results;
  }
  
  public function getInfoFromUrl(string $url, ?UserProfile $profile): ?ProjectVisibilityInfo{
    $user_visibility_clause = $profile === null ? '' : ProjectFilter::getUserVisibilityClause();
    $stmt = $this->db->prepare('SELECT id, name, owner_id, (hidden = FALSE'.$user_visibility_clause.') AS visible FROM projects WHERE url = :url');
    $stmt->bindValue('url', $url);
    
    if ($profile !== null){
      ProjectFilter::bindUserVisibility($stmt, $profile);
    }
    
    $stmt->execute();
    
    $res = $this->fetchOne($stmt);
    return $res === false ? null : new ProjectVisibilityInfo(new ProjectInfo($res['id'], $res['name'], $url, $res['owner_id']), (bool)$res['visible']);
  }
  
  public function checkUrlExists(string $url): bool{
    $stmt = $this->db->prepare('SELECT 1 FROM projects WHERE url = ?');
    $stmt->execute([$url]);
    return (bool)$this->fetchOneColumn($stmt);
  }
  
  public function isHidden(int $id): bool{
    $stmt = $this->db->prepare('SELECT hidden FROM projects WHERE id = ?');
    $stmt->bindValue(1, $id, PDO::PARAM_INT);
    $stmt->execute();
    return (bool)$this->fetchOneColumn($stmt);
  }
  
  public function deleteById(int $id): void{
    $stmt = $this->db->prepare('DELETE FROM projects WHERE id = ?');
    $stmt->bindValue(1, $id, PDO::PARAM_INT);
    $stmt->execute();
  }
}

?>
