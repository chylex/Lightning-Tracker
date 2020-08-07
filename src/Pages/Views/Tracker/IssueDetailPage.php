<?php
declare(strict_types = 1);

namespace Pages\Views\Tracker;

use Pages\Components\DateTimeComponent;
use Pages\Components\Forms\FormComponent;
use Pages\Components\ProgressBarComponent;
use Pages\Components\Text;
use Pages\IViewable;
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
  
  /** @noinspection HtmlMissingClosingTag */
  protected function echoPageBody(): void{
    $issue = $this->model->getIssue();
    
    if ($issue === null){
      $this->echoIssueMissing();
      return;
    }
    
    echo <<<HTML
<div class="split-wrapper">
  <div class="split-80">
    <form action="" method="post">
      <h3>Details</h3>
      <article>
        <div class="issue-details">
HTML;
    
    $milestone = $issue->getMilestoneTitle();
    $author = $issue->getAuthor();
    $assignee = $issue->getAssignee();
    
    $components = [
        'Type'      => $issue->getType()->getViewable(false),
        'Priority'  => $issue->getPriority(),
        'Scale'     => $issue->getScale(),
        'Status'    => $issue->getStatus(),
        'Progress'  => (new ProgressBarComponent($issue->getProgress()))->compact(),
        'Milestone' => Text::plain($milestone === null ? '<span class="missing">none</span>' : $milestone),
        'Author'    => Text::plain($author === null ? '<span class="missing">nobody</span>' : $author->getNameSafe()),
        'Assignee'  => Text::plain($assignee === null ? '<span class="missing">nobody</span>' : $assignee->getNameSafe()),
        'Created'   => new DateTimeComponent($issue->getCreationDate()),
        'Updated'   => new DateTimeComponent($issue->getLastUpdateDate())
    ];
    
    /** @var IViewable $component */
    foreach($components as $title => $component){
      echo <<<HTML
          <div data-title="$title">
            <h4>$title</h4>
HTML;
      
      $component->echoBody();
      
      echo <<<HTML
          </div>
HTML;
    }
    
    echo <<<HTML
        </div>
      </article>
    
      <h3>Description</h3>
      <article class="issue-description">
HTML;
    
    $description = $this->model->getDescription();
    $description->echoBody();
    
    echo <<<HTML
      </article>
HTML;
    
    if ($this->model->canEditCheckboxes() && $description->hasCheckboxes()){
      // TODO hide in JS
      echo <<<HTML
      <h3 data-task-submit>Tasks</h3>
      <article data-task-submit>
        <button class="styled" type="submit"><span class="icon icon-checkmark"></span> Update Tasks</button>
      </article>
HTML;
    }
    
    $action_key = FormComponent::ACTION_KEY;
    $action_value = IssueDetailModel::ACTION_UPDATE_TASKS;
    
    echo <<<HTML
      <input type="hidden" name="$action_key" value="$action_value">
    </form>
  </div>
  <div class="split-20 min-width-250">
HTML;
    
    $this->model->getMenuActions()->echoBody();
    
    echo <<<HTML
  </div>
</div>
HTML;
  }
}

?>
