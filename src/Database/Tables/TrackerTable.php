<?php
declare(strict_types = 1);

namespace Database\Tables;

use Database\AbstractTable;
use Database\Filters\AbstractFilter;
use Database\Filters\Types\TrackerFilter;
use Database\Objects\TrackerInfo;
use Database\Objects\TrackerVisibilityInfo;
use Database\Objects\UserProfile;
use Exception;
use Pages\Models\Tracker\IssuesModel;
use Pages\Models\Tracker\MembersModel;
use Pages\Models\Tracker\MilestonesModel;
use Pages\Models\Tracker\SettingsModel;
use PDO;
use PDOException;

final class TrackerTable extends AbstractTable{
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
  public function addTracker(string $name, string $url, bool $hidden, UserProfile $owner): void{
    $this->db->beginTransaction();
    
    try{
      $stmt = $this->db->prepare('INSERT INTO trackers (name, url, hidden, owner_id) VALUES (:name, :url, :hidden, :owner_id)');
      
      $stmt->bindValue('name', $name);
      $stmt->bindValue('url', $url);
      $stmt->bindValue('hidden', $hidden, PDO::PARAM_BOOL);
      $stmt->bindValue('owner_id', $owner->getId(), PDO::PARAM_INT);
      $stmt->execute();
      
      $id = $this->getLastInsertId();
      
      if ($id === false){
        $this->db->rollBack();
        throw new Exception('Could not retrieve tracker ID.');
      }
      
      $tracker = new TrackerInfo($id, $name, $url, $owner->getId());
      $perms = new TrackerPermTable($this->db, $tracker);
      
      $perms_reporter = [
          IssuesModel::PERM_CREATE
      ];
      
      $perms_moderator = array_merge($perms_reporter, [
          IssuesModel::PERM_EDIT_ALL,
          IssuesModel::PERM_DELETE_ALL,
          MembersModel::PERM_LIST,
          // TODO banning users
      ]);
      
      $perms_admin = array_merge($perms_moderator, [
          MilestonesModel::PERM_EDIT,
          MembersModel::PERM_MANAGE,
          SettingsModel::PERM
      ]);
      
      $perms->addRole('Administrator', $perms_admin);
      $perms->addRole('Moderator', $perms_moderator);
      $perms->addRole('Reporter', $perms_reporter);
      
      $this->db->commit();
    }catch(PDOException $e){
      $this->db->rollBack();
      throw $e;
    }
  }
  
  public function changeSettings(int $id, string $name, bool $hidden): void{
    $stmt = $this->db->prepare('UPDATE trackers SET name = ?, hidden = ? WHERE id = ?');
    $stmt->bindValue(1, $name);
    $stmt->bindValue(2, $hidden, PDO::PARAM_BOOL);
    $stmt->bindValue(3, $id, PDO::PARAM_INT);
    $stmt->execute();
  }
  
  public function countTrackers(?TrackerFilter $filter = null): ?int{
    $filter ??= TrackerFilter::empty();
    
    $stmt = $filter->prepare($this->db, 'SELECT COUNT(*) FROM trackers', AbstractFilter::STMT_COUNT);
    $stmt->execute();
    
    $count = $this->fetchOneColumn($stmt);
    return $count === false ? null : (int)$count;
  }
  
  /**
   * @param TrackerFilter|null $filter
   * @return TrackerInfo[]
   */
  public function listTrackers(?TrackerFilter $filter = null): array{
    $filter ??= TrackerFilter::empty();
    
    $stmt = $filter->prepare($this->db, 'SELECT id, name, url, owner_id FROM trackers');
    $stmt->execute();
    
    $results = [];
    
    while(($res = $this->fetchNext($stmt)) !== false){
      $results[] = new TrackerInfo($res['id'], $res['name'], $res['url'], $res['owner_id']);
    }
    
    return $results;
  }
  
  public function getInfoFromUrl(string $url, ?UserProfile $profile): ?TrackerVisibilityInfo{
    $user_visibility_clause = $profile === null ? '' : TrackerFilter::getUserVisibilityClause();
    $stmt = $this->db->prepare('SELECT id, name, owner_id, (hidden = FALSE'.$user_visibility_clause.') AS visible FROM trackers WHERE url = :url');
    $stmt->bindValue('url', $url);
    
    if ($profile !== null){
      TrackerFilter::bindUserVisibility($stmt, $profile);
    }
    
    $stmt->execute();
    
    $res = $this->fetchOne($stmt);
    return $res === false ? null : new TrackerVisibilityInfo(new TrackerInfo($res['id'], $res['name'], $url, $res['owner_id']), (bool)$res['visible']);
  }
  
  public function checkUrlExists(string $url): bool{
    $stmt = $this->db->prepare('SELECT 1 FROM trackers WHERE url = ?');
    $stmt->execute([$url]);
    return (bool)$this->fetchOneColumn($stmt);
  }
  
  public function isHidden(int $id): bool{
    $stmt = $this->db->prepare('SELECT hidden FROM trackers WHERE id = ?');
    $stmt->bindValue(1, $id, PDO::PARAM_INT);
    $stmt->execute();
    return (bool)$this->fetchOneColumn($stmt);
  }
  
  public function deleteById(int $id): void{
    $stmt = $this->db->prepare('DELETE FROM trackers WHERE id = ?');
    $stmt->bindValue(1, $id, PDO::PARAM_INT);
    $stmt->execute();
  }
}

?>
