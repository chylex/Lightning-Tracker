<?php
declare(strict_types = 1);

use Configuration\SystemConfig;
use Logging\Log;

try{
  if (!copy(CONFIG_FILE, CONFIG_BACKUP_FILE)){
    die('Lightning Tracker tried updating to a new version and failed creating a backup configuration file.');
  }
  
  if (INSTALLED_MIGRATION_VERSION === 1){
    // add migration
  }
  
  $config = SystemConfig::fromCurrentInstallation();
  
  if (!$config->updateDatabaseFeatureSupport()){
    die('Lightning Tracker tried updating to a new version and failed detecting database features.');
  }
  
  if (!file_put_contents(CONFIG_FILE, $config->generate(), LOCK_EX)){
    die('Lightning Tracker tried updating to a new version and failed updating the configuration file.');
  }
}catch(Exception $e){
  Log::critical($e);
  die('Lightning Tracker tried updating to a new version and encountered an unexpected error. Please check the server logs.');
}
?>
