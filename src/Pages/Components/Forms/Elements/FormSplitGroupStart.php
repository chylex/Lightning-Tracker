<?php
declare(strict_types = 1);

namespace Pages\Components\Forms\Elements;

use Pages\IViewable;

final class FormSplitGroupStart implements IViewable{
  private string $split_class;
  private ?string $wrapper_class;
  
  public function __construct(string $split_class, ?string $wrapper_class){
    $this->split_class = $split_class;
    $this->wrapper_class = $wrapper_class;
  }
  
  public function getSplitClass(): string{
    return $this->split_class;
  }
  
  public function echoBody(): void{
    $class = $this->wrapper_class === null ? '' : ' '.$this->wrapper_class;
    
    echo <<<HTML
<div class="split-wrapper$class">
HTML;
  }
}

?>
