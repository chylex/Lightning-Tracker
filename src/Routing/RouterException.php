<?php
declare(strict_types = 1);

namespace Routing;

use Exception;
use Throwable;

final class RouterException extends Exception{
  public const STATUS_FORBIDDEN = 403;
  public const STATUS_NOT_FOUND = 404;
  public const STATUS_SERVER_ERROR = 500;
  
  public function __construct(string $message, int $code, Throwable $previous = null){
    parent::__construct($message, $code, $previous);
  }
}

?>
