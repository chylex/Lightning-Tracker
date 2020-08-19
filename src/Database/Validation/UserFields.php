<?php
declare(strict_types = 1);

namespace Database\Validation;

use Validation\FormValidator;

final class UserFields{
  public static function name(FormValidator $validator): string{
    return $validator->str('Name')->notEmpty()->maxLength(32)->val();
  }
  
  public static function email(FormValidator $validator): string{
    return $validator->str('Email')->notEmpty()->maxLength(191)->contains('@', 'Email is not valid.')->val();
  }
  
  public static function password(FormValidator $validator, string $field = 'Password'): string{
    return $validator->str($field, 'Password')->minLength(7)->maxLength(72)->val();
  }
}

?>
