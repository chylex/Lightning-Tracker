<?php
declare(strict_types = 1);

namespace Session;

use Exception;
use Throwable;

final class PermissionException extends Exception{
  public function __construct(string $permission, int $code = 0, Throwable $previous = null){
    parent::__construct($permission, $code, $previous);
  }
}

?>
