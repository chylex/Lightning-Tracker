<?php
declare(strict_types = 1);

namespace Pages\Views\Mixed;

use Pages\Components\Forms\FormComponent;
use Pages\Models\Mixed\LoginModel;
use Pages\Views\AbstractPage;

class LoginPage extends AbstractPage{
  private LoginModel $model;
  
  public function __construct(LoginModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getHeading(): string{
    return 'Login';
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_MINIMAL;
  }
  
  protected function echoPageHead(): void{
    FormComponent::echoHead();
  }
  
  protected function echoPageBody(): void{
    $this->model->getLoginForm()->echoBody();
  }
}

?>
