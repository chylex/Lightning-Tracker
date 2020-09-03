<?php
declare(strict_types = 1);

namespace Pages\Models;

use Pages\IModel;

class MessageModel extends AbstractWrapperModel{
  private string $title;
  private string $message;
  
  public function __construct(IModel $page_model, string $title, string $message){
    parent::__construct($page_model);
    $this->title = $title;
    $this->message = $message;
  }
  
  public function getTitleSafe(): string{
    return protect($this->title);
  }
  
  public function getMessageSafe(): string{
    return protect($this->message);
  }
}

?>
