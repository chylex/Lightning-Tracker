<?php
declare(strict_types = 1);

namespace Pages\Views\Mixed;

use Pages\Components\Forms\FormComponent;
use Pages\Components\Sidemenu\SidemenuComponent;
use Pages\Components\SplitComponent;
use Pages\Components\Text;
use Pages\IViewable;
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
  
  protected function echoPageHead(): void{
    SplitComponent::echoHead();
    SidemenuComponent::echoHead();
    FormComponent::echoHead();
  }
  
  protected function echoPageBody(): void{
    $split = new SplitComponent(25);
    $split->collapseAt(800);
    $split->setLeftWidthLimits(250);
    
    $split->addLeft(Text::plain('<h3>Menu</h3>'));
    $split->addLeft($this->model->getMenuLinks());
    $split->addLeft($this->model->getMenuActions());
    $split->addRight($this->getAccountPageColumn());
    
    $split->echoBody();
  }
  
  protected function getAccountPageColumn(): IViewable{
    $logon_user = $this->model->getLogonUser();
    
    $form = new FormComponent();
    $form->startTitledSection('General');
    $form->setMessagePlacementHere();
    $form->startSplitGroup(50);
    $form->addTextField('Name')->label('Username')->value($logon_user->getName())->disable();
    $form->addTextField('Email')->value($logon_user->getEmail())->disable();
    $form->endSplitGroup();
    $form->endTitledSection();
    
    return $form;
  }
}

?>
