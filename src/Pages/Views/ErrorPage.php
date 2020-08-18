<?php
declare(strict_types = 1);

namespace Pages\Views;

use Pages\Models\ErrorModel;

final class ErrorPage extends AbstractPage{
  private ErrorModel $model;
  
  public function __construct(ErrorModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getHeading(): string{
    return $this->model->getErrorTitleSafe();
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_MINIMAL;
  }
  
  protected function echoPageBody(): void{
    echo '<p>'.$this->model->getErrorMessageSafe().'</p>';
  }
}

?>
