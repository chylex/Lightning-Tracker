<?php
declare(strict_types = 1);

namespace Pages\Views\Project;

use Pages\Components\Forms\FormComponent;
use Pages\Models\Project\MemberRemoveModel;
use Pages\Views\AbstractProjectPage;

class MemberRemovePage extends AbstractProjectPage{
  private MemberRemoveModel $model;
  
  public function __construct(MemberRemoveModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getSubtitle(): string{
    return 'Members';
  }
  
  protected function getHeading(): string{
    $name = $this->model->hasMember() ? ' - '.$this->model->getMemberNameSafe() : '';
    return self::breadcrumb($this->model->getReq(), 'members').'Remove Member'.$name;
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_COMPACT;
  }
  
  protected function echoPageHead(): void{
    FormComponent::echoHead();
  }
  
  /** @noinspection HtmlMissingClosingTag */
  protected function echoPageBody(): void{
    if ($this->model->hasMember()){
      $name = $this->model->getMemberNameSafe();
      $issue_count = $this->model->getAssignedIssueCount();
      $issue_count_str = $issue_count === 1 ? '1 issue' : $issue_count.' issues';
      
      echo <<<HTML
<h3>Confirm</h3>
<article>
  <p>Member <strong>$name</strong> has $issue_count_str assigned to them. You may choose to keep the current assignments, or reassign the issues to another member or nobody.</p>
  <div class="max-width-400">
HTML;
      
      $this->model->getRemoveForm()->echoBody();
      
      echo <<<HTML
  </div>
</article>
HTML;
    }
    else{
      echo '<p>Member not found.</p>';
    }
  }
}

?>
