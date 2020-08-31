<?php
declare(strict_types = 1);

namespace Pages\Views\Mixed;

use Pages\Components\TitledSectionComponent;
use Pages\IViewable;
use Pages\Models\Mixed\AccountAppearanceModel;

class AccountAppearancePage extends AccountPage{
  private AccountAppearanceModel $model;
  
  public function __construct(AccountAppearanceModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getSubtitle(): string{
    return parent::getSubtitle().' - Appearance';
  }
  
  protected function getAccountPageColumn(): IViewable{
    return new TitledSectionComponent('Appearance', $this->model->getAppearanceSettingsForm());
  }
}

?>
