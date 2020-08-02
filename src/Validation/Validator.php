<?php
declare(strict_types = 1);

namespace Validation;

use Validation\Types\IntValidator;
use Validation\Types\StringValidator;

final class Validator{
  /**
   * @var AbstractFieldValidator[]
   */
  private array $validators = [];
  
  public function str(string $field, string $value, ?string $label = null): StringValidator{
    $validator = new StringValidator($field, $value, $label);
    $this->validators[] = $validator;
    return $validator;
  }
  
  public function int(string $field, int $value, ?string $label = null): IntValidator{
    $validator = new IntValidator($field, $value, $label);
    $this->validators[] = $validator;
    return $validator;
  }
  
  /**
   * @throws ValidationException
   */
  public function validate(){
    $errors = [];
    
    foreach($this->validators as $validator){
      $error = $validator->getError();
      
      if ($error !== null){
        $errors[] = $error;
      }
    }
    
    if (!(empty($errors))){
      throw new ValidationException($errors);
    }
  }
}

?>
