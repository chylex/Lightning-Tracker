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
    return self::LAYOUT_COMPACT;
  }
  
  protected function echoPageHead(){
    FormComponent::echoHead();
  }
  
  protected function echoPageBody(): void{
    if ($this->model->isSuccessfulLogin()){
      echo <<<HTML
<p>Registration successful, you are now logged in!</p>
HTML;
    }
    else{
      $this->model->getForm()->echoBody();
    }
  }
}

?>
