<?php
declare(strict_types = 1);

namespace Database\Validation;

use Validation\FormValidator;

final class ProjectFields{
  public static function name(FormValidator $validator): string{
    return $validator->str('Name')->notEmpty()->maxLength(32)->val();
  }
  
  public static function url(FormValidator $validator): string{
    return $validator->str('Url')->notEmpty()->maxLength(32)->notContains('/')->notContains('\\')->val();
  }
  
  public static function description(FormValidator $validator): string{
    return $validator->str('Description')->maxLength(65000)->val();
  }
  
  public static function hidden(FormValidator $validator): bool{
    return $validator->bool('Hidden')->val();
  }
}

?>
