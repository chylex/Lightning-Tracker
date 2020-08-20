<?php
declare(strict_types = 1);

namespace Pages\Views\Root;

use Pages\Components\Forms\FormComponent;
use Pages\Models\Root\UserDeleteModel;
use Pages\Views\AbstractPage;
use Routing\Link;

class UserDeletePage extends AbstractPage{
  private UserDeleteModel $model;
  
  public function __construct(UserDeleteModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getSubtitle(): string{
    return 'Users';
  }
  
  protected function getHeading(): string{
    $user = $this->model->getUser();
    $name = $user === null ? '' : ' - '.$user->getNameSafe();
    
    return self::breadcrumb($this->model->getReq(), 'users').'Delete User'.$name;
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_COMPACT;
  }
  
  protected function echoPageHead(): void{
    FormComponent::echoHead();
  }
  
  /** @noinspection HtmlMissingClosingTag */
  protected function echoPageBody(): void{
    $user = $this->model->getUser();
    
    if ($user === null){
      echo '<p>User not found.</p>';
      return;
    }
    
    $owned_trackers = $this->model->getOwnedTrackers();
    $owned_tracker_count = count($owned_trackers);
    
    if ($owned_tracker_count > 0){
      $name = $user->getNameSafe();
      $tracker_count_text = $owned_tracker_count === 1 ? '1 tracker' : $owned_tracker_count.' trackers';
      
      echo <<<HTML
<p>User <strong>$name</strong> is the owner of $tracker_count_text and cannot be deleted.</p>
<ul>
HTML;
      
      foreach($owned_trackers as $tracker){
        $url_enc = Link::fromRoot('tracker', rawurlencode($tracker->getUrl()));
        echo '<li><a href="'.$url_enc.'" class="plain">'.$tracker->getNameSafe().' <span class="icon icon-out"></span></a></li>';
      }
      
      echo <<<HTML
</ul>
HTML;
    }
    else{
      $statistics = $this->model->getStatistics();
      
      $tracker_membership_count = $statistics->getTrackerMembershipCount();
      $issues_created_count = $statistics->getIssuesCreatedCount();
      $issues_assigned_count = $statistics->getIssuesAssignedCount();
      
      $deletion_events = [];
      
      if ($tracker_membership_count > 0){
        $title = $tracker_membership_count === 1 ? '1 tracker' : $tracker_membership_count.' trackers';
        $deletion_events[] = 'Their membership in '.$title.' will be removed.';
      }
      
      if ($issues_created_count > 0){
        $title = $issues_created_count === 1 ? '1 issue' : $issues_created_count.' issues';
        $deletion_events[] = 'The authorship of '.$title.' will be reset.';
      }
      
      if ($issues_assigned_count > 0){
        $title = $issues_assigned_count === 1 ? '1 issue' : $issues_assigned_count.' issues';
        $deletion_events[] = 'The assignment of '.$title.' will be reset.';
      }
      
      $deletion_str = '';
      
      if (!empty($deletion_events)){
        $deletion_str = ' If you proceed, the following will happen:</p><ul>';
        
        foreach($deletion_events as $event){
          $deletion_str .= '<li>'.$event.'</li>';
        }
        
        $deletion_str .= '</ul></p>';
      }
      
      echo <<<HTML
<h3>Confirm</h3>
<article>
  <p>Deleting a user cannot be reversed.$deletion_str To confirm deletion, please enter the username:</p>
  <div class="max-width-250">
HTML;
      
      $this->model->getDeleteForm()->echoBody();
      
      echo <<<HTML
  </div>
</article>
HTML;
    }
  }
}

?>
