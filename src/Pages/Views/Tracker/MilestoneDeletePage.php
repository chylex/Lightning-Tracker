<?php
declare(strict_types = 1);

namespace Pages\Views\Tracker;

use Pages\Components\Forms\FormComponent;
use Pages\Models\Tracker\MilestoneDeleteModel;
use Pages\Views\AbstractTrackerPage;

class MilestoneDeletePage extends AbstractTrackerPage{
  private MilestoneDeleteModel $model;
  
  public function __construct(MilestoneDeleteModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getTitle(): string{
    return $this->model->getTracker()->getNameSafe().' - Milestones - Lightning Tracker';
  }
  
  protected function getHeading(): string{
    $base_url = $this->model->getReq()->getBasePath()->encoded();
    return '<a href="'.$base_url.'/milestones">Back</a> <span class="breadcrumb-arrows">&raquo;</span> Delete Milestone';
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_CONDENSED;
  }
  
  protected function echoPageHead(): void{
    FormComponent::echoHead();
  }
  
  /** @noinspection HtmlMissingClosingTag */
  protected function echoPageBody(): void{
    if ($this->model->hasMilestone()){
      $title = $this->model->getMilestoneTitle();
      $issue_count = $this->model->getMilestoneIssueCount();
      $issue_count_str = $issue_count === 1 ? '1 issue' : $issue_count.' issues';
      
      echo <<<HTML
<h3>Confirm</h3>
<article>
  <p>Milestone <strong>$title</strong> has $issue_count_str assigned to it. Please specify which milestone they should be reassigned to.</p>
  <div class="max-width-400">
HTML;
      
      $this->model->getDeleteForm()->echoBody();
      
      echo <<<HTML
  </div>
</article>
HTML;
    }
    else{
      echo '<p>Milestone not found.</p>';
    }
  }
}

?>
