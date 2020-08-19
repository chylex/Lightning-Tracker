<?php
declare(strict_types = 1);

namespace Database\Validation;

use Validation\FormValidator;

final class MilestoneFields{
  public static function title(FormValidator $validator): string{
    return $validator->str('Title')->notEmpty()->maxLength(64)->val();
  }
}

?>
