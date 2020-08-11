<?php
declare(strict_types = 1);

namespace Pages\Views\Tracker;

use Pages\Models\Tracker\IssueDeleteModel;
use Pages\Views\AbstractTrackerIssuePage;

class IssueDeletePage extends AbstractTrackerIssuePage{
  private IssueDeleteModel $model;
  
  public function __construct(IssueDeleteModel $model){
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
    return $this->model->getReq()->getBasePath()->encoded().'/issues/'.$this->model->getIssueId();
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_CONDENSED;
  }
  
  /** @noinspection HtmlMissingClosingTag */
  protected function echoPageBody(): void{
    $issue = $this->model->getIssue();
    
    if ($issue === null){
      $this->echoIssueMissing();
      return;
    }
    
    echo <<<HTML
<h3>Confirm</h3>
<article>
  <p>Deleting an issue cannot be reversed. To confirm deletion, please enter the issue ID.</p>
  <div class="max-width-250">
HTML;
    
    $this->model->getConfirmationForm()->echoBody();
    
    echo <<<HTML
  </div>
</article>
HTML;
  }
}

?>
