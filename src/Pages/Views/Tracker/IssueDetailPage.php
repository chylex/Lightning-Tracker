<?php
declare(strict_types = 1);

namespace Pages\Views\Tracker;

use Pages\Components\Html;
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
  
  protected function getSubtitle(): string{
    return 'Issue #'.$this->model->getIssueId();
  }
  
  protected function getHeading(): string{
    $issue = $this->model->getIssue();
    $title = $issue === null ? '' : ' - '.$issue->getTitleSafe();
    
    return parent::getHeading().' '.$this->getSubtitle().$title;
  }
  
  protected function getHeadingBackUrl(): string{
    return 'issues';
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
    
    $menus = array_filter([$this->model->getMenuActions(), $this->model->getMenuShortcuts()], fn($v): bool => $v !== null);
    
    if (!empty($menus)){
      $split->addRight(new Html('<h3>Actions</h3>'));
      
      foreach($menus as $menu){
        $split->addRight($menu);
      }
    }
    
    $split->addRightIfNotNull($this->model->getActiveMilestoneComponent());
    
    $split->echoBody();
  }
}

?>
