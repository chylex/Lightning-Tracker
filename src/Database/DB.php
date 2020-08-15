<?php
declare(strict_types = 1);

namespace Database;

use PDO;
use PDOException;

final class DB{
  private const MYSQL_INIT = <<<SQL
SET time_zone = "+00:00",
    sql_mode = "STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION"
SQL;
  
  /**
   * @return PDO
   * @throws PDOException
   */
  public static function get(): PDO{
    static $db;
    
    if (!isset($db)){
      $db = self::from(DB_DRIVER, DB_NAME, DB_HOST, DB_USER, DB_PASSWORD);
    }
    
    return $db;
  }
  
  public static function from(string $driver, string $name, string $host, string $user, string $password): PDO{
    return new PDO($driver.':dbname='.$name.';host='.$host.';charset=utf8mb4', $user, $password, [
        PDO::MYSQL_ATTR_INIT_COMMAND => self::MYSQL_INIT,
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false
    ]);
  }
  
  public static function close(): void{
    static $db;
    unset($db);
  }
}

?>
