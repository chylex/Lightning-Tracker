<?php
declare(strict_types = 1);

namespace Database\Tables;

use Database\AbstractTable;
use Database\Objects\TrackerInfo;
use Database\Objects\TrackerVisibilityInfo;
use Database\Objects\UserProfile;
use PDO;

final class TrackerTable extends AbstractTable{
  public function __construct(PDO $db){
    parent::__construct($db);
  }
  
  public function getInfoFromUrl(string $url, ?UserProfile $profile /* TODO */): ?TrackerVisibilityInfo{
    $stmt = $this->db->prepare('SELECT id, name, (hidden = FALSE) AS visible FROM trackers WHERE url = :url');
    $stmt->bindValue('url', $url);
    $stmt->execute();
    
    $res = $this->fetchOne($stmt);
    return $res === false ? null : new TrackerVisibilityInfo(new TrackerInfo($res['id'], $res['name'], $url), (bool)$res['visible']);
  }
}

?>
