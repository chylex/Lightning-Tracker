<?php
declare(strict_types = 1);

namespace Pages\Components\Forms\Elements;

use Pages\Components\Text;
use Pages\IViewable;

final class FormMessageList implements IViewable{
  private array $messages = [];
  
  public function addMessage(string $level, Text $text): void{
    $this->messages[] = [$level, $text->getHtml()];
  }
  
  public function appendMessages(array $messages): void{
    array_push($this->messages, ...$messages);
  }
  
  public function getMessages(): array{
    return $this->messages;
  }
  
  public function echoBody(): void{
    foreach($this->messages as [$level, $text]){
      echo '<p class="message '.$level.'">'.$text.'</p>';
    }
  }
}

?>
