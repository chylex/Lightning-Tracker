<?php
declare(strict_types = 1);

namespace Pages\Views;

use Pages\Models\MessageModel;

final class MessagePage extends AbstractPage{
  private MessageModel $model;
  
  public function __construct(MessageModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getHeading(): string{
    return $this->model->getTitleSafe();
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_MINIMAL;
  }
  
  protected function echoPageBody(): void{
    echo '<p>'.$this->model->getMessageSafe().'</p>';
  }
}

?>
