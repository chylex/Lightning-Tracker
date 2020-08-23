<?php
declare(strict_types = 1);

namespace Pages\Components\Issues;

use Pages\Components\DateTimeComponent;
use Pages\Components\Forms\FormComponent;
use Pages\Components\ProgressBarComponent;
use Pages\Components\Text;
use Pages\IViewable;
use Pages\Models\Tracker\IssueDetailModel;

final class IssueDetailComponent implements IViewable{
  private IssueDetailModel $model;
  
  public function __construct(IssueDetailModel $model){
    $this->model = $model;
  }
  
  /** @noinspection HtmlMissingClosingTag */
  public function echoBody(): void{
    $issue = $this->model->getIssue();
    
    echo <<<HTML
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
        'Milestone' => $milestone === null ? Text::missing('None') : Text::plain($milestone),
        'Author'    => $author === null ? Text::missing('Nobody') : Text::plain($author->getName()),
        'Assignee'  => $assignee === null ? Text::missing('Nobody') : Text::plain($assignee->getName()),
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
HTML;
  }
}

?>
