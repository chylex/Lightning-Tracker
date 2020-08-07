<?php
declare(strict_types = 1);

namespace Pages\Views\Mixed;

use Pages\Models\Mixed\AccountSecurityModel;

class AccountSecurityPage extends AccountPage{
  private AccountSecurityModel $model;
  
  public function __construct(AccountSecurityModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getTitle(): string{
    return parent::getTitle().' - Security';
  }
  
  protected function echoAccountPageColumn(): void{
    echo <<<HTML
<h3>Change Password</h3>
<article>
HTML;
    
    $this->model->getChangePasswordForm()->echoBody();
    
    echo <<<HTML
</article>
HTML;
  }
}

?>
