<?php
declare(strict_types = 1);

namespace Pages\Components\Forms\Elements;

use Pages\IViewable;

final class FormHiddenValue implements IViewable{
  private string $key;
  private string $value;
  
  public function __construct(string $key, string $value){
    $this->key = $key;
    $this->value = $value;
  }
  
  public function echoBody(): void{
    echo <<<HTML
<input type="hidden" name="$this->key" value="$this->value">
HTML;
  }
}

?>
