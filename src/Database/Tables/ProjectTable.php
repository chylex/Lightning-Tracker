<?php
declare(strict_types = 1);

namespace Database\Tables;

use Data\UserId;
use Database\AbstractTable;
use Database\Filters\AbstractFilter;
use Database\Filters\Types\ProjectFilter;
use Database\Objects\ProjectInfo;
use Database\Objects\ProjectVisibilityInfo;
use Database\Objects\UserProfile;
use Exception;
use PDOException;
use Session\Permissions\ProjectPermissions;
use Session\Permissions\SystemPermissions;

final class ProjectTable extends AbstractTable{
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
      $this->execute('INSERT INTO projects (name, url, description, hidden, owner_id) VALUES (?, ?, \'\', ?, ?)',
                     'SSBS', [$name, $url, $hidden, $owner->getId()]);
      
      $id = $this->getLastInsertId();
      
      if ($id === null){
        $this->db->rollBack();
        throw new Exception('Could not retrieve project ID.');
      }
      
      $project = new ProjectInfo($id, $name, $url, $owner->getId());
      $roles = new ProjectRoleTable($this->db, $project);
      $perms = new ProjectRolePermTable($this->db, $project);
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
          ProjectPermissions::MANAGE_MEMBERS,
          ProjectPermissions::VIEW_SETTINGS
      ]);
      
      $perms_admin = array_merge($perms_moderator, [
          ProjectPermissions::MANAGE_SETTINGS_GENERAL,
          ProjectPermissions::MANAGE_SETTINGS_DESCRIPTION,
          ProjectPermissions::MANAGE_SETTINGS_ROLES,
      ]);
      
      $owner_role_id = $roles->addRole('Owner', true);
      $administrator_role_id = $roles->addRole('Administrator');
      $moderator_role_id = $roles->addRole('Moderator');
      $developer_role_id = $roles->addRole('Developer');
      $reporter_role_id = $roles->addRole('Reporter');
      
      $perms->addRolePermissions($administrator_role_id, $perms_admin);
      $perms->addRolePermissions($moderator_role_id, $perms_moderator);
      $perms->addRolePermissions($developer_role_id, $perms_developer);
      $perms->addRolePermissions($reporter_role_id, $perms_reporter);
      
      $members->addMember($owner->getId(), $owner_role_id);
      
      $this->db->commit();
    }catch(PDOException $e){
      $this->db->rollBack();
      throw $e;
    }
  }
  
  public function changeSettings(int $id, string $name, bool $hidden): void{
    $this->execute('UPDATE projects SET name = ?, hidden = ? WHERE id = ?',
                   'SBI', [$name, $hidden, $id]);
  }
  
  public function countProjects(?ProjectFilter $filter = null): ?int{
    $filter ??= ProjectFilter::empty();
    
    $stmt = $filter->prepare($this->db, 'SELECT COUNT(*) FROM projects', AbstractFilter::STMT_COUNT);
    $stmt->execute();
    return $this->fetchOneInt($stmt);
  }
  
  /**
   * @param ProjectFilter|null $filter
   * @return ProjectInfo[]
   */
  public function listProjects(?ProjectFilter $filter = null): array{
    $filter ??= ProjectFilter::empty();
    
    $stmt = $filter->prepare($this->db, 'SELECT id, name, url, owner_id FROM projects');
    $stmt->execute();
    return $this->fetchMap($stmt, fn($v): ProjectInfo => new ProjectInfo($v['id'], $v['name'], $v['url'], UserId::fromRaw($v['owner_id'])));
  }
  
  /**
   * @param UserId $user_id
   * @return ProjectInfo[]
   */
  public function listProjectsOwnedBy(UserId $user_id): array{
    $stmt = $this->db->prepare('SELECT id, name, url FROM projects WHERE owner_id = ?');
    $stmt->execute([$user_id]);
    return $this->fetchMap($stmt, fn($v): ProjectInfo => new ProjectInfo($v['id'], $v['name'], $v['url'], $user_id));
  }
  
  public function getInfoFromUrl(string $url, ?UserProfile $profile, SystemPermissions $perms): ?ProjectVisibilityInfo{
    $user_visibility_clause = $profile === null ? '' : ProjectFilter::getUserVisibilityClause();
    $stmt = $this->db->prepare('SELECT id, name, owner_id, (hidden = FALSE'.$user_visibility_clause.') AS visible FROM projects WHERE url = :url');
    $stmt->bindValue('url', $url);
    
    if ($profile !== null){
      ProjectFilter::bindUserVisibility($stmt, $profile);
    }
    
    $stmt->execute();
    $res = $this->fetchOneRaw($stmt);
    
    if ($res === false){
      return null;
    }
    
    $project = new ProjectInfo($res['id'], $res['name'], $url, UserId::fromRaw($res['owner_id']));
    $visible = (bool)$res['visible'] || $perms->check(SystemPermissions::LIST_ALL_PROJECTS);
    
    return new ProjectVisibilityInfo($project, $visible);
  }
  
  public function setDescription(int $id, string $description): void{
    $this->execute('UPDATE projects SET description = ? WHERE id = ?',
                   'SI', [$description, $id]);
  }
  
  public function getDescription(int $id): ?string{
    $stmt = $this->execute('SELECT description FROM projects WHERE id = ?',
                           'I', [$id]);
    
    return $this->fetchOneColumn($stmt);
  }
  
  public function checkUrlExists(string $url): bool{
    $stmt = $this->execute('SELECT 1 FROM projects WHERE url = ?',
                           'S', [$url]);
    
    return (bool)$this->fetchOneColumn($stmt);
  }
  
  public function isHidden(int $id): bool{
    $stmt = $this->execute('SELECT hidden FROM projects WHERE id = ?',
                           'I', [$id]);
    
    return (bool)$this->fetchOneColumn($stmt);
  }
  
  public function deleteById(int $id): void{
    $this->execute('DELETE FROM projects WHERE id = ?',
                   'I', [$id]);
  }
}

?>
