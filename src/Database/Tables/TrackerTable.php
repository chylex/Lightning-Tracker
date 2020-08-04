<?php
declare(strict_types = 1);

namespace Database\Tables;

use Database\AbstractTable;
use Database\Filters\Types\TrackerFilter;
use Database\Objects\TrackerInfo;
use Database\Objects\TrackerVisibilityInfo;
use Database\Objects\UserProfile;
use PDO;

final class TrackerTable extends AbstractTable{
  public function __construct(PDO $db){
    parent::__construct($db);
  }
  
  public function addTracker(string $name, string $url, bool $hidden, UserProfile $owner): void{
    $stmt = $this->db->prepare(<<<SQL
INSERT INTO trackers (name, url, hidden, owner)
VALUES (:name, :url, :hidden, :owner_id)
SQL
    );
    
    $stmt->bindValue('name', $name);
    $stmt->bindValue('url', $url);
    $stmt->bindValue('hidden', $hidden, PDO::PARAM_BOOL);
    $stmt->bindValue('owner_id', $owner->getId(), PDO::PARAM_INT);
    $stmt->execute();
  }
  
  public function countTrackers(TrackerFilter $filter = null): ?int{
    $filter ??= TrackerFilter::empty();
    
    $stmt = $this->db->prepare('SELECT COUNT(*) FROM trackers '.$filter->generateClauses(true));
    $filter->prepareStatement($stmt);
    $stmt->execute();
    
    $count = $this->fetchOneColumn($stmt);
    return $count === false ? null : (int)$count;
  }
  
  /**
   * @param TrackerFilter|null $filter
   * @return TrackerInfo[]
   */
  public function listTrackers(TrackerFilter $filter = null): array{
    $filter ??= TrackerFilter::empty();
    
    $stmt = $this->db->prepare('SELECT id, name, url FROM trackers '.$filter->generateClauses());
    $filter->prepareStatement($stmt);
    $stmt->execute();
    
    $results = [];
    
    while(($res = $this->fetchNext($stmt)) !== false){
      $results[] = new TrackerInfo($res['id'], $res['name'], $res['url']);
    }
    
    return $results;
  }
  
  public function getInfoFromUrl(string $url, ?UserProfile $profile): ?TrackerVisibilityInfo{
    $user_visibility_clause = $profile === null ? '' : TrackerFilter::getUserVisibilityClause();
    $stmt = $this->db->prepare('SELECT id, name, (hidden = FALSE'.$user_visibility_clause.') AS visible FROM trackers WHERE url = :url');
    $stmt->bindValue('url', $url);
    
    if ($profile !== null){
      $stmt->bindValue('user_id', $profile->getId());
    }
    
    $stmt->execute();
    
    $res = $this->fetchOne($stmt);
    return $res === false ? null : new TrackerVisibilityInfo(new TrackerInfo($res['id'], $res['name'], $url), (bool)$res['visible']);
  }
  
  public function checkUrlExists(string $url): bool{
    $stmt = $this->db->prepare('SELECT 1 FROM trackers WHERE url = ?');
    $stmt->execute([$url]);
    return (bool)$this->fetchOneColumn($stmt);
  }
  
  public function setHidden(int $id, bool $hidden){
    $stmt = $this->db->prepare('UPDATE trackers SET hidden = ? WHERE id = ?');
    $stmt->bindValue(1, $hidden, PDO::PARAM_BOOL);
    $stmt->bindValue(2, $id, PDO::PARAM_INT);
    $stmt->execute();
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
