<?php
declare(strict_types = 1);

namespace Database\Validation;

use Validation\FormValidator;

final class RoleFields{
  public static function title(FormValidator $validator): string{
    return $validator->str('Title')->notEmpty()->maxLength(32)->val();
  }
}

?>
