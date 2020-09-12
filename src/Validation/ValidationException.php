<?php
declare(strict_types = 1);

namespace Validation;

use Exception;
use Throwable;

final class ValidationException extends Exception{
  /**
   * @var InvalidField[]
   */
  private array $fields;
  
  public function __construct($fields, $code = 0, Throwable $previous = null){
    parent::__construct('Validation failed.', $code, $previous);
    $this->fields = $fields;
  }
  
  public function __toString(): string{
    return implode("\n", array_map(static fn($field): string => $field->getMessage(), $this->fields));
  }
  
  /**
   * @return InvalidField[]
   */
  public function getFields(): array{
    return $this->fields;
  }
}

?>
