<?php
declare(strict_types = 1);

use Configuration\SystemConfig;
use Data\UserId;
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
  
  if ($migration_version === 6){
    $db = DB::get();
    
    $stmt = $db->prepare(<<<SQL
SELECT DISTINCT TABLE_NAME AS tbl, CONSTRAINT_NAME AS constr
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = :db_name AND REFERENCED_TABLE_SCHEMA = TABLE_SCHEMA
SQL
    );
    
    $stmt->bindValue('db_name', DB_NAME);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    
    foreach($rows as $row){
      /** @noinspection SqlResolve */
      $db->exec('ALTER TABLE `'.$row['tbl'].'` DROP FOREIGN KEY `'.$row['constr'].'`');
    }
    
    $db->exec('ALTER TABLE users ADD public_id CHAR(9) NOT NULL FIRST');
    
    $db->exec('ALTER TABLE issues CHANGE author_id author_id_old INT NULL');
    $db->exec('ALTER TABLE issues CHANGE assignee_id assignee_id_old INT NULL');
    $db->exec('ALTER TABLE project_members CHANGE user_id user_id_old INT NOT NULL');
    $db->exec('ALTER TABLE projects CHANGE owner_id owner_id_old INT NOT NULL');
    $db->exec('ALTER TABLE project_user_settings CHANGE user_id user_id_old INT NOT NULL');
    $db->exec('ALTER TABLE user_logins CHANGE id id_old INT NOT NULL');
    
    $db->exec('ALTER TABLE project_members DROP PRIMARY KEY');
    $db->exec('ALTER TABLE project_user_settings DROP PRIMARY KEY');
    $db->exec('ALTER TABLE user_logins DROP PRIMARY KEY');
  
    /** @noinspection SqlResolve */
    $db->exec('ALTER TABLE issues ADD author_id CHAR(9) NULL AFTER author_id_old');
    /** @noinspection SqlResolve */
    $db->exec('ALTER TABLE issues ADD assignee_id CHAR(9) NULL AFTER assignee_id_old');
    /** @noinspection SqlResolve */
    $db->exec('ALTER TABLE project_members ADD user_id CHAR(9) NOT NULL AFTER user_id_old');
    /** @noinspection SqlResolve */
    $db->exec('ALTER TABLE projects ADD owner_id CHAR(9) NOT NULL AFTER owner_id_old');
    /** @noinspection SqlResolve */
    $db->exec('ALTER TABLE project_user_settings ADD user_id CHAR(9) NOT NULL AFTER user_id_old');
    /** @noinspection SqlResolve */
    $db->exec('ALTER TABLE user_logins ADD id CHAR(9) NOT NULL AFTER id_old');
    
    $stmt = $db->query('SELECT id FROM users');
    
    while(($res = $stmt->fetchColumn()) !== false){
      /** @noinspection SqlResolve */
      $s2 = $db->prepare('UPDATE users SET public_id = ? WHERE id = ?');
      $s2->bindValue(1, UserId::generateNew());
      $s2->bindValue(2, (int)$res, PDO::PARAM_INT);
      $s2->execute();
    }
  
    /** @noinspection SqlResolve, SqlWithoutWhere */
    $db->exec('UPDATE issues SET author_id = (SELECT u.public_id FROM users u WHERE u.id = author_id_old)');
    /** @noinspection SqlResolve, SqlWithoutWhere */
    $db->exec('UPDATE issues SET assignee_id = (SELECT u.public_id FROM users u WHERE u.id = assignee_id_old)');
    /** @noinspection SqlResolve, SqlWithoutWhere */
    $db->exec('UPDATE project_members SET user_id = (SELECT u.public_id FROM users u WHERE u.id = user_id_old)');
    /** @noinspection SqlResolve, SqlWithoutWhere */
    $db->exec('UPDATE projects SET owner_id = (SELECT u.public_id FROM users u WHERE u.id = owner_id_old)');
    /** @noinspection SqlResolve, SqlWithoutWhere */
    $db->exec('UPDATE project_user_settings SET user_id = (SELECT u.public_id FROM users u WHERE u.id = user_id_old)');
    /** @noinspection SqlResolve, SqlWithoutWhere */
    $db->exec('UPDATE user_logins SET id = (SELECT u.public_id FROM users u WHERE u.id = id_old)');
    
    $db->exec('ALTER TABLE users DROP id');
    /** @noinspection SqlResolve */
    $db->exec('ALTER TABLE users CHANGE public_id id CHAR(9) NOT NULL');
    $db->exec('ALTER TABLE users ADD PRIMARY KEY (id)');
  
    $db->exec('ALTER TABLE project_members ADD PRIMARY KEY (project_id, user_id)');
    $db->exec('ALTER TABLE project_user_settings ADD PRIMARY KEY (project_id, user_id)');
    $db->exec('ALTER TABLE user_logins ADD PRIMARY KEY (id, token)');
    
    $db->exec('ALTER TABLE issues ADD CONSTRAINT fk__issue__project FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON UPDATE CASCADE ON DELETE CASCADE');
    $db->exec('ALTER TABLE issues ADD CONSTRAINT fk__issue__author FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL');
    $db->exec('ALTER TABLE issues ADD CONSTRAINT fk__issue__assignee FOREIGN KEY (`assignee_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL');
    $db->exec('ALTER TABLE issues ADD CONSTRAINT fk__issue__milestone FOREIGN KEY (`milestone_id`, `project_id`) REFERENCES `milestones` (`milestone_id`, `project_id`) ON UPDATE CASCADE ON DELETE RESTRICT');
    $db->exec('ALTER TABLE issues ADD CONSTRAINT fk__issue__scale FOREIGN KEY (`scale`) REFERENCES `issue_weights` (`scale`) ON UPDATE RESTRICT ON DELETE RESTRICT');
    $db->exec('ALTER TABLE milestones ADD CONSTRAINT fk__milestone__project FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON UPDATE CASCADE ON DELETE CASCADE');
    $db->exec('ALTER TABLE project_members ADD CONSTRAINT fk__project_member__project FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON UPDATE CASCADE ON DELETE CASCADE');
    $db->exec('ALTER TABLE project_members ADD CONSTRAINT fk__project_member__user FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE');
    $db->exec('ALTER TABLE project_members ADD CONSTRAINT fk__project_member__role FOREIGN KEY (`role_id`, `project_id`) REFERENCES `project_roles` (`role_id`, `project_id`) ON UPDATE CASCADE ON DELETE RESTRICT');
    $db->exec('ALTER TABLE project_role_permissions ADD CONSTRAINT fk__project_role_permission__role FOREIGN KEY (`role_id`, `project_id`) REFERENCES `project_roles` (`role_id`, `project_id`) ON UPDATE CASCADE ON DELETE CASCADE');
    $db->exec('ALTER TABLE project_roles ADD CONSTRAINT fk__project_role__project FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON UPDATE CASCADE ON DELETE CASCADE ');
    $db->exec('ALTER TABLE projects ADD CONSTRAINT fk__project__owner FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT');
    $db->exec('ALTER TABLE project_user_settings ADD CONSTRAINT fk__project_user_setting__user FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE');
    $db->exec('ALTER TABLE project_user_settings ADD CONSTRAINT fk__project_user_setting__active_milestone FOREIGN KEY (`active_milestone`, `project_id`) REFERENCES `milestones` (`milestone_id`, `project_id`) ON UPDATE CASCADE ON DELETE CASCADE');
    $db->exec('ALTER TABLE system_role_permissions ADD CONSTRAINT fk__system_role_permission__role FOREIGN KEY (`role_id`) REFERENCES `system_roles` (`id`) ON UPDATE CASCADE ON DELETE CASCADE');
    $db->exec('ALTER TABLE user_logins ADD CONSTRAINT fk__user_login__user FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE');
    $db->exec('ALTER TABLE users ADD CONSTRAINT fk__user__role FOREIGN KEY (`role_id`) REFERENCES `system_roles` (`id`) ON UPDATE CASCADE ON DELETE SET NULL');
  
    /** @noinspection SqlResolve */
    $db->exec('ALTER TABLE issues DROP author_id_old');
    /** @noinspection SqlResolve */
    $db->exec('ALTER TABLE issues DROP assignee_id_old');
    /** @noinspection SqlResolve */
    $db->exec('ALTER TABLE project_members DROP user_id_old');
    /** @noinspection SqlResolve */
    $db->exec('ALTER TABLE projects DROP owner_id_old');
    /** @noinspection SqlResolve */
    $db->exec('ALTER TABLE project_user_settings DROP user_id_old');
    /** @noinspection SqlResolve */
    $db->exec('ALTER TABLE user_logins DROP id_old');
    
    upgrade_config($db, $migration_version = 7);
  }
}catch(Exception $e){
  if (isset($db) && $db->inTransaction()){
    $db->rollBack();
  }
  
  Log::critical($e);
  die('Lightning Tracker tried updating to a new version and encountered an unexpected error. Please check the server logs.');
}
?>
