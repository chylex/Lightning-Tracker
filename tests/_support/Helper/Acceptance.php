<?php
declare(strict_types = 1);

namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use Codeception\Module;
use Database\DB;
use FilesystemIterator;
use PDO;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Acceptance extends Module{
  private static PDO $db;
  
  public static function getDB(): PDO{
    if (isset(self::$db)){
      return self::$db;
    }
    
    define('DB_DRIVER', 'mysql');
    define('DB_NAME', 'tracker_test');
    define('DB_HOST', 'localhost');
    define('DB_USER', 'lt');
    define('DB_PASSWORD', 'test');
    
    require __DIR__.'/../../../src/Database/DB.php';
    return self::$db = DB::get();
  }
  
  public static function isInGroup(string $group): bool{
    $argv = $_SERVER['argv'];
    $k = array_search('-g', $argv, true);
    
    if ($k === false){
      array_search('--group', $argv, true);
    }
    
    return $k !== false && $argv[$k + 1] === $group;
  }
  
  public function _beforeSuite($settings = []): void{
    $db = self::getDB();
    
    $tables = [
        'system_roles',
        'system_role_permissions',
        'users',
        'user_logins',
        'projects',
        'project_roles',
        'project_role_permissions',
        'project_members',
        'milestones',
        'issue_weights',
        'issues',
        'project_user_settings',
    ];
    
    foreach(array_reverse($tables) as $file => $table){
      $db->exec('DROP TABLE IF EXISTS '.$table);
    }
  }
  
  public function _afterSuite(): void{
    $dir_backup = __DIR__.'/../../../server/www-backup';
    $dir_www = __DIR__.'/../../../server/www';
    $dir_tmp = $dir_www.'-tmp';
    $dir_c3 = $dir_www.'/tests/_output/c3tmp';
    
    if (is_dir($dir_backup) && !is_dir($dir_c3)){
      $delete = new RecursiveDirectoryIterator($dir_www, FilesystemIterator::SKIP_DOTS);
      
      foreach(new RecursiveIteratorIterator($delete, RecursiveIteratorIterator::CHILD_FIRST) as $path){
        if ($path->isFile()){
          unlink($path->getPathname());
        }
        else{
          rmdir($path->getPathname());
        }
      }
      
      // replacing the folder immediately breaks on Windows for some reason...
      rename($dir_www, $dir_tmp);
      rmdir($dir_tmp);
      
      rename($dir_backup, $dir_www);
    }
  }
}
