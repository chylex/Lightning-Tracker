<?php
declare(strict_types = 1);

namespace Database\Tables;

use Data\UserId;
use Database\AbstractProjectTable;
use Database\Objects\MilestoneProgress;

final class ProjectUserSettingsTable extends AbstractProjectTable{
  public function toggleActiveMilestone(UserId $user_id, int $milestone_id): void{
    $sql = <<<SQL
INSERT INTO project_user_settings (project_id, user_id, active_milestone)
VALUES (?, ?, ?)
ON DUPLICATE KEY UPDATE active_milestone = IF(active_milestone = VALUES(active_milestone), NULL, VALUES(active_milestone))
SQL;
    
    $this->execute($sql, 'ISI', [$this->getProjectId(), $user_id, $milestone_id]);
  }
  
  public function getActiveMilestoneProgress(UserId $user_id): ?MilestoneProgress{
    $sql = <<<SQL
SELECT m.milestone_id                                                  AS id,
       m.title                                                         AS title,
       FLOOR(SUM(i.progress * iw.contribution) / SUM(iw.contribution)) AS percentage_done
FROM project_user_settings tus
JOIN      milestones m ON tus.active_milestone = m.milestone_id AND tus.project_id = m.project_id
LEFT JOIN issues i ON m.milestone_id = i.milestone_id AND m.project_id = i.project_id
LEFT JOIN issue_weights iw ON i.scale = iw.scale
WHERE tus.user_id = ? AND tus.project_id = ?
GROUP BY m.milestone_id
SQL;
    
    $stmt = $this->execute($sql, 'SI', [$user_id, $this->getProjectId()]);
    $res = $this->fetchOneRaw($stmt);
    return $res === false ? null : new MilestoneProgress($res['id'], $res['title'], $res['percentage_done'] === null ? null : (int)$res['percentage_done']);
  }
}

?>
