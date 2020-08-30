<?php
declare(strict_types = 1);

use Database\DB;
use Logging\Log;
use Update\AbstractMigrationProcess;
use Update\MigrationManager;
use Update\Migrations\Migration6;

function get_migration(int $id): ?AbstractMigrationProcess{
  switch($id){
    case 6:
      return new Migration6();
      
    default:
      return null;
  }
}

$manager = new MigrationManager(MIGRATION_VERSION, MIGRATION_TASK);

try{
  $db = DB::get();
}catch(Exception $e){
  die('Lightning Tracker tried updating to a new version but could not connect to the database.');
}

try{
  while(($version = $manager->getCurrentVersion()) < TRACKER_MIGRATION_VERSION){
    $migration = get_migration($version);
    
    if ($migration === null){
      die('Cannot automatically update the installed version.');
    }
    
    $tasks = $migration->getTasks();
    
    while(($id = $manager->getCurrentTask()) < count($tasks)){
      $task = $tasks[$id];
      $task->prepare($db);
      $task->execute($db);
      $task->finalize($db);
      $manager->finishTask();
    }
    
    $manager->finishVersion();
  }
}catch(Exception $e){
  Log::critical($e);
  
  if ($db->inTransaction()){
    $db->rollBack();
  }
  
  die('Lightning Tracker tried updating to a new version and encountered an unexpected error (migration '.$manager->getCurrentVersion().', task '.$manager->getCurrentTask().'). Please check the server logs.');
}
?>
