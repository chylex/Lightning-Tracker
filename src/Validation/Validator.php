<?php
declare(strict_types = 1);

namespace Validation;

use Validation\Types\StringValidator;

final class Validator extends AbstractValidator{
  public function str(string $field, string $value, ?string $label = null): StringValidator{
    $validator = new StringValidator($field, $value, $label);
    $this->validators[] = $validator;
    return $validator;
  }
}

?>
