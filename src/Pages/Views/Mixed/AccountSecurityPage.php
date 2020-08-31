<?php
declare(strict_types = 1);

namespace Pages\Views\Mixed;

use Pages\Components\TitledSectionComponent;
use Pages\IViewable;
use Pages\Models\Mixed\AccountSecurityModel;

class AccountSecurityPage extends AccountPage{
  private AccountSecurityModel $model;
  
  public function __construct(AccountSecurityModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getSubtitle(): string{
    return parent::getSubtitle().' - Security';
  }
  
  protected function getAccountPageColumn(): IViewable{
    return new TitledSectionComponent('Change Password', $this->model->getChangePasswordForm());
  }
}

?>
