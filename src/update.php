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

/**
 * @param string $path
 * @return string
 * @throws Exception
 */
function read_sql_file(string $path): string{
  $file = __DIR__.'/~database/'.$path;
  $contents = file_get_contents($file);
  
  if ($contents === false){
    throw new Exception('Error reading file \''.$path.'\'.');
  }
  
  return $contents;
}

try{
  if (!copy(CONFIG_FILE, CONFIG_BACKUP_FILE)){
    die('Lightning Tracker tried updating to a new version and failed creating a backup configuration file.');
  }
  
  $migration_version = INSTALLED_MIGRATION_VERSION;
  
  if ($migration_version === 1){
    $db = DB::get();
    
    $db->exec('ALTER TABLE system_roles ADD special BOOL DEFAULT FALSE NOT NULL');
    /** @noinspection SqlResolve */
    $db->exec('ALTER TABLE tracker_roles ADD special BOOL DEFAULT FALSE NOT NULL');
    
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
      $db->exec('ALTER TABLE `'.$row['tbl'].'` DROP FOREIGN KEY `'.$row['constr'].'`');
    }
    
    /** @noinspection SqlResolve */
    $db->exec('ALTER TABLE milestones CHANGE id milestone_id INT NOT NULL AFTER tracker_id');
    $db->exec('ALTER TABLE milestones DROP PRIMARY KEY');
    /** @noinspection SqlResolve */
    $db->exec('ALTER TABLE milestones ADD PRIMARY KEY (tracker_id, milestone_id)');
    
    /** @noinspection SqlResolve */
    $db->exec(<<<SQL
ALTER TABLE issues
  ADD FOREIGN KEY (`milestone_id`, `tracker_id`)
    REFERENCES `milestones` (`milestone_id`, `tracker_id`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
SQL
    );
    
    /** @noinspection SqlResolve */
    $db->exec(<<<SQL
ALTER TABLE tracker_user_settings
  ADD FOREIGN KEY (`active_milestone`, `tracker_id`)
    REFERENCES `milestones` (`milestone_id`, `tracker_id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE
SQL
    );
    
    begin_transaction($db);
    
    /** @noinspection SqlResolve */
    $db->exec(<<<SQL
INSERT INTO tracker_roles (tracker_id, title, special)
SELECT tracker_id, 'Owner' AS title, TRUE AS special
FROM tracker_roles
GROUP BY tracker_id
SQL
    );
    
    /** @noinspection SqlResolve */
    $db->exec(<<<SQL
INSERT INTO tracker_members (tracker_id, user_id, role_id)
SELECT t.id AS tracker_id, t.owner_id AS user_id, tr.id AS role_id
FROM trackers t
JOIN tracker_roles tr ON t.id = tr.tracker_id AND tr.title = 'Owner' AND tr.special = TRUE
SQL
    );
    
    /** @noinspection SqlWithoutWhere */
    $db->exec('UPDATE milestones SET milestone_id = ordering');
    
    upgrade_config($db, $migration_version = 2);
  }
  
  if ($migration_version === 2){
    $db = DB::get();
    
    $db->exec('ALTER TABLE milestones MODIFY ordering MEDIUMINT NOT NULL');
    
    $stmt = $db->prepare(<<<SQL
SELECT DISTINCT TABLE_NAME AS tbl, CONSTRAINT_NAME AS constr
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = :db_name
  AND REFERENCED_TABLE_SCHEMA = TABLE_SCHEMA
  AND (TABLE_NAME = 'tracker_members' AND REFERENCED_TABLE_NAME = 'tracker_roles')
SQL
    );
    
    $stmt->bindValue('db_name', DB_NAME);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    
    foreach($rows as $row){
      /** @noinspection SqlResolve */
      $db->exec('ALTER TABLE `'.$row['tbl'].'` DROP FOREIGN KEY `'.$row['constr'].'`');
    }
    
    /** @noinspection SqlResolve */
    $db->exec('DROP TABLE tracker_role_perms');
    /** @noinspection SqlResolve */
    $db->exec('DROP TABLE tracker_roles');
    
    $db->exec(read_sql_file('TrackerRoleTable.sql'));
    $db->exec(read_sql_file('TrackerRolePermTable.sql'));
    
    /** @noinspection SqlResolve */
    $db->exec('UPDATE tracker_members SET role_id = NULL');
    
    /** @noinspection SqlResolve */
    $db->exec(<<<SQL
ALTER TABLE tracker_members
  ADD FOREIGN KEY (`role_id`, `tracker_id`)
    REFERENCES `tracker_roles` (`role_id`, `tracker_id`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
SQL
    );
    
    begin_transaction($db);
    
    /** @noinspection SqlResolve */
    $db->exec(<<<SQL
INSERT INTO tracker_roles (tracker_id, role_id, title, ordering, special)
SELECT t.id, 1 AS role_id, 'Owner' AS title, 0 AS ordering, TRUE AS special
FROM trackers t
GROUP BY t.id
SQL
    );
    
    /** @noinspection SqlResolve */
    $db->exec(<<<SQL
INSERT INTO tracker_members (tracker_id, user_id, role_id)
SELECT t.id AS tracker_id, t.owner_id AS user_id, tr.role_id AS role_id
FROM trackers t
JOIN tracker_roles tr ON t.id = tr.tracker_id AND tr.title = 'Owner' AND tr.special = TRUE
ON DUPLICATE KEY UPDATE role_id = tr.role_id
SQL
    );
    
    upgrade_config($db, $migration_version = 3);
  }
  
  if ($migration_version === 3){
    $db = DB::get();
    
    /** @noinspection SqlResolve */
    $db->exec('ALTER TABLE tracker_role_perms MODIFY permission ENUM (\'settings\', \'members.list\', \'members.manage\', \'milestones.manage\', \'issues.create\', \'issues.fields.all\', \'issues.edit.all\', \'issues.delete.all\') NOT NULL');
    
    begin_transaction($db);
    
    /** @noinspection SqlResolve */
    $db->exec(<<<SQL
INSERT IGNORE INTO tracker_role_perms (tracker_id, role_id, permission)
SELECT tr.tracker_id AS tracker_id, tr.role_id AS role_id, 'issues.fields.all' AS permission
FROM tracker_roles tr
WHERE tr.title = 'Developer'
SQL
    );
    
    upgrade_config($db, $migration_version = 4);
  }
  
  if ($migration_version === 4){
    $db = DB::get();
    
    $db->exec('RENAME TABLE trackers TO projects');
    $db->exec('RENAME TABLE tracker_roles TO project_roles');
    $db->exec('RENAME TABLE tracker_role_perms TO project_role_perms');
    $db->exec('RENAME TABLE tracker_members TO project_members');
    $db->exec('RENAME TABLE tracker_user_settings TO project_user_settings');
    
    /** @noinspection SqlResolve */
    $db->exec('ALTER TABLE project_roles CHANGE tracker_id project_id INT NOT NULL');
    /** @noinspection SqlResolve */
    $db->exec('ALTER TABLE project_role_perms CHANGE tracker_id project_id INT NOT NULL');
    /** @noinspection SqlResolve */
    $db->exec('ALTER TABLE project_members CHANGE tracker_id project_id INT NOT NULL');
    /** @noinspection SqlResolve */
    $db->exec('ALTER TABLE project_user_settings CHANGE tracker_id project_id INT NOT NULL');
    /** @noinspection SqlResolve */
    $db->exec('ALTER TABLE issues CHANGE tracker_id project_id INT NOT NULL');
    /** @noinspection SqlResolve */
    $db->exec('ALTER TABLE milestones CHANGE tracker_id project_id INT NOT NULL');
    
    upgrade_config($db, $migration_version = 5);
  }
  
  if ($migration_version === 5){
    $db = DB::get();
    
    $db->exec('RENAME TABLE project_role_perms TO project_role_permissions');
    $db->exec('RENAME TABLE system_role_perms TO system_role_permissions');
    $db->exec('ALTER TABLE system_role_permissions MODIFY permission ENUM (\'settings\', \'projects.list\', \'projects.list.all\', \'projects.create\', \'projects.manage\', \'users.list\', \'users.view.emails\', \'users.create\', \'users.manage\') NOT NULL');
    
    upgrade_config($db, $migration_version = 6);
  }
}catch(Exception $e){
  if (isset($db) && $db->inTransaction()){
    $db->rollBack();
  }
  
  Log::critical($e);
  die('Lightning Tracker tried updating to a new version and encountered an unexpected error. Please check the server logs.');
}
?>
