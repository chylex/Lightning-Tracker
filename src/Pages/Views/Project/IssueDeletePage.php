<?php
declare(strict_types = 1);

namespace Pages\Views\Project;

use Pages\Models\Project\IssueDeleteModel;
use Pages\Views\AbstractProjectIssuePage;

class IssueDeletePage extends AbstractProjectIssuePage{
  private IssueDeleteModel $model;
  
  public function __construct(IssueDeleteModel $model){
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
    return 'issues/'.$this->model->getIssueId();
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
