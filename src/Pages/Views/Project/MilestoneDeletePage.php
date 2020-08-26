<?php
declare(strict_types = 1);

namespace Pages\Views\Project;

use Pages\Components\Forms\FormComponent;
use Pages\Models\Project\MilestoneDeleteModel;
use Pages\Views\AbstractProjectPage;

class MilestoneDeletePage extends AbstractProjectPage{
  private MilestoneDeleteModel $model;
  
  public function __construct(MilestoneDeleteModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getSubtitle(): string{
    return 'Milestones';
  }
  
  protected function getHeading(): string{
    $name = $this->model->hasMilestone() ? ' - '.$this->model->getMilestoneTitleSafe() : '';
    return self::breadcrumb($this->model->getReq(), 'milestones').'Delete Milestone'.$name;
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_COMPACT;
  }
  
  protected function echoPageHead(): void{
    FormComponent::echoHead();
  }
  
  /** @noinspection HtmlMissingClosingTag */
  protected function echoPageBody(): void{
    if ($this->model->hasMilestone()){
      $title = $this->model->getMilestoneTitleSafe();
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
