<?php
declare(strict_types = 1);

namespace Pages\Views\Tracker;

use Pages\Components\Issues\IssueDetailComponent;
use Pages\Components\SplitComponent;
use Pages\Models\Tracker\IssueDetailModel;
use Pages\Views\AbstractTrackerIssuePage;

class IssueDetailPage extends AbstractTrackerIssuePage{
  private IssueDetailModel $model;
  
  public function __construct(IssueDetailModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getTitle(): string{
    return $this->model->getTracker()->getNameSafe().' - Issue #'.$this->model->getIssueId().' - Lightning Tracker';
  }
  
  protected function getHeading(): string{
    $issue_id = $this->model->getIssueId();
    $issue = $this->model->getIssue();
    
    $title = $issue === null ? '' : ' - '.$issue->getTitleSafe();
    return parent::getHeading().' Issue #'.$issue_id.$title;
  }
  
  protected function getHeadingBackUrl(): string{
    return $this->model->getReq()->getBasePath()->encoded().'/issues';
  }
  
  protected function echoPageBody(): void{
    $issue = $this->model->getIssue();
    
    if ($issue === null){
      $this->echoIssueMissing();
      return;
    }
    
    $split = new SplitComponent(80);
    $split->collapseAt(800);
    $split->setRightWidthLimits(250, 400);
    
    $split->addLeft(new IssueDetailComponent($this->model));
    $split->addRightIfNotNull($this->model->getMenuActions());
    $split->addRightIfNotNull($this->model->getActiveMilestoneComponent());
    
    $split->echoBody();
  }
}

?>
