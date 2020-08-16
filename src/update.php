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

try{
  if (!copy(CONFIG_FILE, CONFIG_BACKUP_FILE)){
    die('Lightning Tracker tried updating to a new version and failed creating a backup configuration file.');
  }
  
  if (INSTALLED_MIGRATION_VERSION === 1){
    $db = DB::get();
    $db->query('ALTER TABLE system_roles ADD special BOOL DEFAULT FALSE NOT NULL');
    $db->query('ALTER TABLE tracker_roles ADD special BOOL DEFAULT FALSE NOT NULL');
    
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
  }
  
  if (!file_put_contents(CONFIG_FILE, SystemConfig::fromCurrentInstallation()->generate(), LOCK_EX)){
    die('Lightning Tracker tried updating to a new version and failed updating the configuration file.');
  }
  
  if (isset($db) && $db->inTransaction()){
    $db->commit();
  }
}catch(Exception $e){
  if (isset($db) && $db->inTransaction()){
    $db->rollBack();
  }
  
  Log::critical($e);
  die('Lightning Tracker tried updating to a new version and encountered an unexpected error. Please check the server logs.');
}
?>
