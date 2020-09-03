<?php
declare(strict_types = 1);

namespace Pages\Views\Mixed;

use Pages\Components\Forms\FormComponent;
use Pages\Models\Mixed\RegisterModel;
use Pages\Views\AbstractPage;

class RegisterPage extends AbstractPage{
  private RegisterModel $model;
  
  public function __construct(RegisterModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getHeading(): string{
    return 'Register';
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_MINIMAL;
  }
  
  protected function echoPageHead(): void{
    FormComponent::echoHead();
  }
  
  protected function echoPageBody(): void{
    $this->model->getRegisterForm()->echoBody();
  }
}

?>
