<?php
declare(strict_types = 1);

namespace Database\Tables;

use Database\AbstractProjectTable;
use Database\Objects\MilestoneProgress;
use Database\Objects\UserProfile;
use PDO;

final class ProjectUserSettingsTable extends AbstractProjectTable{
  public function toggleActiveMilestone(UserProfile $user, int $milestone_id): void{
    $stmt = $this->db->prepare(<<<SQL
INSERT INTO project_user_settings (project_id, user_id, active_milestone)
VALUES (?, ?, ?)
ON DUPLICATE KEY UPDATE active_milestone = IF(active_milestone = VALUES(active_milestone), NULL, VALUES(active_milestone))
SQL
    );
    
    $stmt->bindValue(1, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->bindValue(2, $user->getId(), PDO::PARAM_INT);
    $stmt->bindValue(3, $milestone_id, PDO::PARAM_INT);
    $stmt->execute();
  }
  
  public function getActiveMilestoneProgress(UserProfile $user): ?MilestoneProgress{
    $stmt = $this->db->prepare(<<<SQL
SELECT m.milestone_id                                                  AS id,
       m.title                                                         AS title,
       FLOOR(SUM(i.progress * iw.contribution) / SUM(iw.contribution)) AS percentage_done
FROM project_user_settings tus
JOIN      milestones m ON tus.project_id = m.project_id AND tus.active_milestone = m.milestone_id
LEFT JOIN issues i ON m.milestone_id = i.milestone_id
LEFT JOIN issue_weights iw ON i.scale = iw.scale
WHERE tus.user_id = ? AND tus.project_id = ?
GROUP BY m.milestone_id
SQL
    );
    
    $stmt->bindValue(1, $user->getId(), PDO::PARAM_INT);
    $stmt->bindValue(2, $this->getProjectId(), PDO::PARAM_INT);
    $stmt->execute();
    
    $res = $this->fetchOne($stmt);
    return $res === false ? null : new MilestoneProgress($res['id'], $res['title'], $res['percentage_done'] === null ? null : (int)$res['percentage_done']);
  }
}

?>
