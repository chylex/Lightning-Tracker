<?php
declare(strict_types = 1);

namespace Pages\Views\Tracker;

use Pages\Models\Tracker\IssueEditModel;
use Pages\Views\AbstractTrackerIssuePage;

class IssueEditPage extends AbstractTrackerIssuePage{
  private IssueEditModel $model;
  
  public function __construct(IssueEditModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getTitle(): string{
    if ($this->model->isNewIssue()){
      return $this->model->getTracker()->getNameSafe().' - New Issue - Lightning Tracker';
    }
    else{
      return $this->model->getTracker()->getNameSafe().' - Issue #'.$this->model->getIssueId().' - Lightning Tracker';
    }
  }
  
  protected function getHeading(): string{
    $issue_id = $this->model->getIssueId();
    $issue = $this->model->getIssue();
    
    if ($this->model->isNewIssue()){
      return parent::getHeading().' New Issue';
    }
    elseif ($issue === null){
      return parent::getHeading().' Issue #'.$issue_id;
    }
    else{
      return parent::getHeading().' Issue #'.$issue_id.' - '.$issue->getTitleSafe();
    }
  }
  
  protected function getHeadingBackUrl(): string{
    $issue_id = $this->model->getIssueId();
    $base_path = $this->model->getReq()->getBasePath()->encoded();
    
    if ($issue_id === null){
      return $base_path.'/issues';
    }
    else{
      return $base_path.'/issues/'.$issue_id;
    }
  }
  
  protected function echoPageBody(): void{
    if (!$this->model->isNewIssue() && $this->model->getIssue() === null){
      $this->echoIssueMissing();
      return;
    }
    
    $this->model->getForm()->echoBody();
  }
}

?>
