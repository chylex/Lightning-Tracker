<?php
declare(strict_types = 1);

namespace Pages\Views\Root;

use Pages\Components\Forms\FormComponent;
use Pages\Components\SplitComponent;
use Pages\Models\Root\UserEditModel;
use Pages\Views\AbstractPage;

class UserEditPage extends AbstractPage{
  private UserEditModel $model;
  
  public function __construct(UserEditModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getSubtitle(): string{
    return 'Users';
  }
  
  protected function getHeading(): string{
    $user = $this->model->getUser();
    $name = $user === null ? '' : ' - '.$user->getNameSafe();
    
    return self::breadcrumb($this->model->getReq(), 'users').'Edit User'.$name;
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_COMPACT;
  }
  
  protected function echoPageHead(): void{
    FormComponent::echoHead();
    SplitComponent::echoHead();
  }
  
  protected function echoPageBody(): void{
    if ($this->model->getUser() === null){
      echo '<p>User not found.</p>';
    }
    else{
      $this->model->getEditForm()->echoBody();
    }
  }
}

?>
