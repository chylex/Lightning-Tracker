<?php
declare(strict_types = 1);

namespace Pages\Views\Project;

use Pages\Components\Forms\FormComponent;
use Pages\Models\Project\SettingsRoleEditModel;
use Pages\Views\AbstractProjectPage;

class SettingsRoleEditPage extends AbstractProjectPage{
  private SettingsRoleEditModel $model;
  
  public function __construct(SettingsRoleEditModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getSubtitle(): string{
    return 'Settings - Roles';
  }
  
  protected function getHeading(): string{
    $title = $this->model->hasRole() ? ' - '.$this->model->getRoleTitleSafe() : '';
    return self::breadcrumb($this->model->getReq(), 'settings/roles').'Edit Role'.$title;
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_COMPACT;
  }
  
  protected function echoPageHead(): void{
    parent::echoPageHead();
    FormComponent::echoHead();
  }
  
  protected function echoPageBody(): void{
    if ($this->model->hasRole()){
      $this->model->getEditForm()->echoBody();
    }
    else{
      echo '<p>Role not found.</p>';
    }
  }
}

?>
