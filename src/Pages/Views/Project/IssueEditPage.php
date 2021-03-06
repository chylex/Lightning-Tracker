<?php
declare(strict_types = 1);

namespace Pages\Views\Project;

use Pages\Components\Forms\Elements\FormLightMarkEditor;
use Pages\Models\Project\IssueEditModel;
use Pages\Views\AbstractProjectIssuePage;

class IssueEditPage extends AbstractProjectIssuePage{
  private IssueEditModel $model;
  
  public function __construct(IssueEditModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getSubtitle(): string{
    if ($this->model->isNewIssue()){
      return 'New Issue';
    }
    else{
      return 'Issue #'.$this->model->getIssueId();
    }
  }
  
  protected function getHeading(): string{
    $issue = $this->model->isNewIssue() ? null : $this->model->getIssue();
    $title = $issue === null ? '' : ' - '.$issue->getTitleSafe();
    
    return parent::getHeading().' '.$this->getSubtitle().$title;
  }
  
  protected function getHeadingBackUrl(): string{
    if ($this->model->isNewIssue()){
      return 'issues';
    }
    else{
      return 'issues/'.$this->model->getIssueId();
    }
  }
  
  protected function echoPageHead(): void{
    parent::echoPageHead();
    FormLightMarkEditor::echoHead();
  }
  
  protected function echoPageBody(): void{
    if (!$this->model->isNewIssue() && $this->model->getIssue() === null){
      $this->echoIssueMissing();
      return;
    }
    
    $this->model->getEditForm()->echoBody();
  }
}

?>
