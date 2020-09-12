<?php
declare(strict_types = 1);

namespace Pages\Views\Project;

use Pages\Components\Html;
use Pages\Components\Issues\IssueDetailComponent;
use Pages\Components\Markup\LightMarkComponent;
use Pages\Components\SplitComponent;
use Pages\Models\Project\IssueDetailModel;
use Pages\Views\AbstractProjectIssuePage;

class IssueDetailPage extends AbstractProjectIssuePage{
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
  
  protected function echoPageHead(): void{
    parent::echoPageHead();
    LightMarkComponent::echoHead();
    
    echo '<script type="text/javascript" src="~resources/js/issuedetail.js?v='.TRACKER_RESOURCE_VERSION.'"></script>';
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
    
    $menus = array_filter([$this->model->createMenuActions(), $this->model->createMenuShortcuts()], static fn($v): bool => $v !== null);
    
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
