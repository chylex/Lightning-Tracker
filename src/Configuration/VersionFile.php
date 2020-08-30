<?php
declare(strict_types = 1);

namespace Configuration;

final class VersionFile{
  private int $migration_version;
  private int $migration_task;
  
  public function __construct(int $migration_version, int $migration_task){
    $this->migration_version = $migration_version;
    $this->migration_task = $migration_task;
  }
  
  public function generate(): string{
    /** @noinspection ALL */
    $contents = <<<PHP
<?php
declare(strict_types = 1);

define('MIGRATION_VERSION', $this->migration_version);
define('MIGRATION_TASK', $this->migration_task);
?>
PHP;
    
    return $contents;
  }
  
  public function writeSafe(string $target_file, string $tmp_file): bool{
    return file_put_contents($tmp_file, $this->generate(), LOCK_EX) && rename($tmp_file, $target_file);
  }
}

?>
