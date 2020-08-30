<?php
declare(strict_types = 1);

namespace Update;

use Configuration\VersionFile;
use Exception;

final class MigrationManager{
  private int $current_version;
  private int $current_task;
  
  public function __construct(int $current_version, int $current_task){
    $this->current_version = $current_version;
    $this->current_task = $current_task;
  }
  
  public function getCurrentVersion(): int{
    return $this->current_version;
  }
  
  public function getCurrentTask(): int{
    return $this->current_task;
  }
  
  /**
   * @throws Exception
   */
  public function finishVersion(): void{
    ++$this->current_version;
    $this->current_task = 0;
    $this->writeFile();
  }
  
  /**
   * @throws Exception
   */
  public function finishTask(): void{
    ++$this->current_task;
    $this->writeFile();
  }
  
  /**
   * @throws Exception
   */
  private function writeFile(): void{
    if (!(new VersionFile($this->current_version, $this->current_task))->writeSafe(VERSION_FILE, VERSION_TMP_FILE)){
      throw new Exception('Error updating version file (migration '.$this->current_version.', task '.$this->current_task.').');
    }
  }
}

?>
