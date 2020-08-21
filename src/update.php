<?php
declare(strict_types = 1);

use Configuration\SystemConfig;
use Database\DB;
use Logging\Log;

function begin_transaction(PDO $db): void{
  if (!$db->inTransaction()){
    $db->beginTransaction();
  }
}

function upgrade_config(PDO $db, int $version): void{
  if (!file_put_contents(CONFIG_FILE, SystemConfig::fromCurrentInstallation()->generate($version), LOCK_EX)){
    die('Lightning Tracker tried updating to a new version and failed updating the configuration file.');
  }
  
  if (isset($db) && $db->inTransaction()){
    $db->commit();
  }
}

try{
  if (!copy(CONFIG_FILE, CONFIG_BACKUP_FILE)){
    die('Lightning Tracker tried updating to a new version and failed creating a backup configuration file.');
  }
  
  if (INSTALLED_MIGRATION_VERSION === 1){
    $db = DB::get();
    
    $db->query('ALTER TABLE system_roles ADD special BOOL DEFAULT FALSE NOT NULL');
    $db->query('ALTER TABLE tracker_roles ADD special BOOL DEFAULT FALSE NOT NULL');
    
    $stmt = $db->prepare(<<<SQL
SELECT DISTINCT TABLE_NAME AS tbl, CONSTRAINT_NAME AS constr
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = :db_name AND REFERENCED_TABLE_SCHEMA = TABLE_SCHEMA
  AND (
    (TABLE_NAME = 'issues' AND COLUMN_NAME = 'milestone_id') OR
    (TABLE_NAME = 'tracker_user_settings' AND COLUMN_NAME = 'active_milestone') OR
    (TABLE_NAME = 'tracker_user_settings' AND COLUMN_NAME = 'tracker_id')
  )
SQL
    );
    
    $stmt->bindValue('db_name', DB_NAME);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    
    foreach($rows as $row){
      /** @noinspection SqlResolve */
      $db->query('ALTER TABLE `'.$row['tbl'].'` DROP FOREIGN KEY `'.$row['constr'].'`');
    }
    
    /** @noinspection SqlResolve */
    $db->query('ALTER TABLE milestones CHANGE id milestone_id INT NOT NULL AFTER tracker_id');
    $db->query('ALTER TABLE milestones DROP PRIMARY KEY');
    $db->query('ALTER TABLE milestones ADD PRIMARY KEY (tracker_id, milestone_id)');
    
    $db->query(<<<SQL
ALTER TABLE issues
  ADD FOREIGN KEY (`milestone_id`, `tracker_id`)
    REFERENCES `milestones` (`milestone_id`, `tracker_id`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
SQL
    );
    
    $db->query(<<<SQL
ALTER TABLE tracker_user_settings
  ADD FOREIGN KEY (`active_milestone`, `tracker_id`)
    REFERENCES `milestones` (`milestone_id`, `tracker_id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE
SQL
    );
    
    begin_transaction($db);
    
    $db->query(<<<SQL
INSERT INTO tracker_roles (tracker_id, title, special)
SELECT tracker_id, 'Owner' AS title, TRUE AS special
FROM tracker_roles
GROUP BY tracker_id
SQL
    );
    
    $db->query(<<<SQL
INSERT INTO tracker_members (tracker_id, user_id, role_id)
SELECT t.id AS tracker_id, t.owner_id AS user_id, tr.id AS role_id
FROM trackers t
JOIN tracker_roles tr ON t.id = tr.tracker_id AND tr.title = 'Owner' AND tr.special = TRUE
SQL
    );
    
    /** @noinspection SqlWithoutWhere */
    $db->query('UPDATE milestones SET milestone_id = ordering');
    
    upgrade_config($db, 2);
  }
  
  if (INSTALLED_MIGRATION_VERSION === 2){
    $db = DB::get();
    
    $db->query('ALTER TABLE tracker_roles ADD ordering MEDIUMINT NOT NULL AFTER title');
    
    begin_transaction($db);
    
    $db->query(<<<SQL
INSERT INTO tracker_roles (tracker_id, title, ordering)
SELECT tracker_id, 'Developer' AS title, 3
FROM tracker_roles
GROUP BY tracker_id
SQL
    );
    
    // TODO reset permissions
    
    $db->query('UPDATE tracker_roles SET ordering = 0 WHERE title = \'Owner\'');
    $db->query('UPDATE tracker_roles SET ordering = 1 WHERE title = \'Administrator\'');
    $db->query('UPDATE tracker_roles SET ordering = 2 WHERE title = \'Moderator\'');
    $db->query('UPDATE tracker_roles SET ordering = 4 WHERE title = \'Reporter\'');
    
    upgrade_config($db, 3);
  }
}catch(Exception $e){
  if (isset($db) && $db->inTransaction()){
    $db->rollBack();
  }
  
  Log::critical($e);
  die('Lightning Tracker tried updating to a new version and encountered an unexpected error. Please check the server logs.');
}
?>
