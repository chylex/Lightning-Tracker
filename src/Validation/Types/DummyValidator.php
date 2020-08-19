<?php
declare(strict_types = 1);

namespace Validation\Types;

use Validation\AbstractFieldValidator;

class DummyValidator extends AbstractFieldValidator{
  /**
   * @var mixed
   */
  private $value;
  
  public function __construct(string $field, $value, ?string $label){
    parent::__construct($field, $label);
    $this->value = $value;
  }
  
  public function val(){
    return $this->value;
  }
}

?>
