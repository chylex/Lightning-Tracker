<?php
declare(strict_types = 1);

namespace Pages\Views\Mixed;

use Pages\Components\Forms\FormComponent;
use Pages\Components\Sidemenu\SidemenuComponent;
use Pages\Models\Mixed\AccountModel;
use Pages\Views\AbstractPage;

class AccountPage extends AbstractPage{
  private AccountModel $model;
  
  public function __construct(AccountModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getTitle(): string{
    return 'Lightning Tracker - My Account';
  }
  
  protected function getHeading(): string{
    return '';
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_CONDENSED;
  }
  
  protected function echoPageHead(){
    SidemenuComponent::echoHead();
    FormComponent::echoHead();
  }
  
  /** @noinspection HtmlMissingClosingTag */
  protected function echoPageBody(): void{
    echo <<<HTML
<div class="split-wrapper">
  <div class="split-25">
    <h3>Menu</h3>
HTML;
    
    $this->model->getMenuLinks()->echoBody();
    $this->model->getMenuActions()->echoBody();
    
    echo <<<HTML
  </div>
  <main class="split-75">
HTML;
    
    $this->echoAccountPageColumn();
    
    echo <<<HTML
  </main>
</div>
HTML;
  }
  
  protected function echoAccountPageColumn(){
    $logon_user = $this->model->getLogonUser();
    
    $form = new FormComponent();
    $form->startSplitGroup(50);
    $form->addTextField('Name')->label('Username')->value($logon_user->getNameSafe())->disabled();
    $form->addTextField('Email')->value($logon_user->getEmailSafe())->disabled();
    $form->endSplitGroup();
    
    echo <<<HTML
<h3>General</h3>
<article>
HTML;
    
    $form->echoBody();
    
    echo <<<HTML
</article>
HTML;
  }
}

?>
