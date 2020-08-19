<?php
declare(strict_types = 1);

namespace Validation;

abstract class AbstractValidator{
  /**
   * @var AbstractFieldValidator[]
   */
  protected array $validators = [];
  
  /**
   * @throws ValidationException
   */
  public function validate(): void{
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
