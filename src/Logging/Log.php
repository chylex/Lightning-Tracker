<?php
declare(strict_types = 1);

namespace Logging;

use Exception;

final class Log{
  public static function critical(Exception $e): void{
    error_log($e->getMessage());
  }
}

?>
