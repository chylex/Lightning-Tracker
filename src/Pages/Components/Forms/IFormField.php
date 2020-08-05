<?php
declare(strict_types = 1);

namespace Pages\Components\Forms;

use Pages\IViewable;

interface IFormField extends IViewable{
  public function value(string $value): IFormField;
  public function disable(): IFormField;
  
  public function getName(): string;
  public function addError(string $message): void;
  
  public function isDisabled(): bool;
  public function acceptsMissingField(): bool;
}

?>
