<?php
declare(strict_types = 1);

namespace Pages\Views\Project;

use Pages\Components\Forms\FormComponent;
use Pages\Models\Project\MemberEditModel;
use Pages\Views\AbstractProjectPage;

class MemberEditPage extends AbstractProjectPage{
  private MemberEditModel $model;
  
  public function __construct(MemberEditModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getSubtitle(): string{
    return 'Members';
  }
  
  protected function getHeading(): string{
    $name = $this->model->hasMember() ? ' - '.$this->model->getMemberNameSafe() : '';
    return self::breadcrumb($this->model->getReq(), 'members').'Edit Member'.$name;
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_COMPACT;
  }
  
  protected function echoPageHead(): void{
    FormComponent::echoHead();
  }
  
  protected function echoPageBody(): void{
    if ($this->model->hasMember()){
      echo '<div class="max-width-400">';
      $this->model->getEditForm()->echoBody();
      echo '</div>';
    }
    else{
      echo '<p>Member not found.</p>';
    }
  }
}

?>
