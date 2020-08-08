<?php
declare(strict_types = 1);

namespace Database\Tables;

use Database\AbstractTrackerTable;
use Database\Objects\MilestoneProgress;
use Database\Objects\TrackerInfo;
use Database\Objects\UserProfile;
use PDO;

final class TrackerUserSettingsTable extends AbstractTrackerTable{
  public function __construct(PDO $db, TrackerInfo $tracker){
    parent::__construct($db, $tracker);
  }
  
  public function toggleActiveMilestone(UserProfile $user, int $milestone_id): void{
    $stmt = $this->db->prepare(<<<SQL
INSERT INTO tracker_user_settings (tracker_id, user_id, active_milestone)
VALUES (?, ?, ?)
ON DUPLICATE KEY UPDATE active_milestone = IF(active_milestone = VALUES(active_milestone), NULL, VALUES(active_milestone))
SQL
    );
    
    $stmt->bindValue(1, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->bindValue(2, $user->getId(), PDO::PARAM_INT);
    $stmt->bindValue(3, $milestone_id, PDO::PARAM_INT);
    $stmt->execute();
  }
  
  public function getActiveMilestoneProgress(UserProfile $user): ?MilestoneProgress{
    $stmt = $this->db->prepare(<<<SQL
SELECT m.id                                                            AS id,
       m.title                                                         AS title,
       FLOOR(SUM(i.progress * iw.contribution) / SUM(iw.contribution)) AS percentage_done
FROM tracker_user_settings tus
JOIN      milestones m ON m.id = tus.active_milestone
LEFT JOIN issues i ON m.id = i.milestone_id
LEFT JOIN issue_weights iw ON i.scale = iw.scale
WHERE tus.user_id = ? AND tus.tracker_id = ?
GROUP BY m.id
SQL
    );
    
    $stmt->bindValue(1, $user->getId(), PDO::PARAM_INT);
    $stmt->bindValue(2, $this->getTrackerId(), PDO::PARAM_INT);
    $stmt->execute();
    
    $res = $this->fetchOne($stmt);
    return $res === false ? null : new MilestoneProgress($res['id'], $res['title'], $res['percentage_done'] === null ? null : (int)$res['percentage_done']);
  }
}

?>
