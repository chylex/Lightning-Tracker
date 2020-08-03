<?php
declare(strict_types = 1);

namespace Pages\Models;

use Pages\IModel;
use function Database\protect;

class ErrorModel extends AbstractWrapperModel{
  private string $title;
  private string $message;
  
  public function __construct(IModel $page_model, string $title, string $message){
    parent::__construct($page_model);
    $this->title = $title;
    $this->message = $message;
  }
  
  public function getErrorTitleSafe(): string{
    return protect($this->title);
  }
  
  public function getErrorMessageSafe(): string{
    return protect($this->message);
  }
}

?>
