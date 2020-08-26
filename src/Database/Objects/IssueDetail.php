<?php
declare(strict_types = 1);

namespace Database\Objects;

use Pages\Components\Issues\IssuePriority;
use Pages\Components\Issues\IssueScale;
use Pages\Components\Issues\IssueStatus;
use Pages\Components\Issues\IssueType;
use Pages\Components\Markdown\MarkdownComponent;
use Session\Permissions\TrackerPermissions;

final class IssueDetail extends IssueInfo{
  public const EDIT_FORBIDDEN = 0;
  public const EDIT_BASIC_FIELDS = 1;
  public const EDIT_ALL_FIELDS = 2;
  
  private string $description;
  private ?int $milestone_id;
  private ?string $milestone_title;
  private ?IssueUser $author;
  private ?IssueUser $assignee;
  
  public function __construct(int $id,
                              string $title,
                              string $description,
                              IssueType $type,
                              IssuePriority $priority,
                              IssueScale $scale,
                              IssueStatus $status,
                              int $progress,
                              string $date_created,
                              string $date_updated,
                              ?int $milestone_id,
                              ?string $milestone_title,
                              ?IssueUser $author,
                              ?IssueUser $assignee
  ){
    parent::__construct($id, $title, $type, $priority, $scale, $status, $progress, $date_created, $date_updated);
    $this->description = $description;
    $this->milestone_id = $milestone_id;
    $this->milestone_title = $milestone_title;
    $this->author = $author;
    $this->assignee = $assignee;
  }
  
  public function getDescription(): MarkdownComponent{
    return new MarkdownComponent($this->description);
  }
  
  public function getMilestoneId(): ?int{
    return $this->milestone_id;
  }
  
  public function getMilestoneTitle(): ?string{
    return $this->milestone_title;
  }
  
  public function getAuthor(): ?IssueUser{
    return $this->author;
  }
  
  public function getAssignee(): ?IssueUser{
    return $this->assignee;
  }
  
  public function isAuthorOrAssignee(UserProfile $user): bool{
    $user_id = $user->getId();
    $author = $this->author;
    $assignee = $this->assignee;
    
    return ($author !== null && $user_id === $author->getId()) || ($assignee !== null && $user_id === $assignee->getId());
  }
  
  public function isAssignee(UserProfile $user): bool{
    return $this->assignee !== null && $user->getId() === $this->assignee->getId();
  }
  
  public function getEditLevel(?UserProfile $user, TrackerPermissions $perms): int{
    $can_edit = $this->isAuthorOrAssignee($user) || $perms->check(TrackerPermissions::EDIT_ALL_ISSUES);
    
    if (!$can_edit){
      return self::EDIT_FORBIDDEN;
    }
    
    $all_fields = $this->isAssignee($user) || $perms->check(TrackerPermissions::MODIFY_ALL_ISSUE_FIELDS);
    return $all_fields ? self::EDIT_ALL_FIELDS : self::EDIT_BASIC_FIELDS;
  }
}

?>
