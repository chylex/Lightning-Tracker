<?php
declare(strict_types = 1);

namespace Database\Objects;

use Data\IssuePriority;
use Data\IssueScale;
use Data\IssueStatus;
use Data\IssueType;
use Pages\Components\Markup\LightMarkComponent;
use Session\Permissions\ProjectPermissions;

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
  
  public function getDescription(): LightMarkComponent{
    return new LightMarkComponent($this->description);
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
    
    return ($author !== null && $user_id->equals($author->getId())) || ($assignee !== null && $user_id->equals($assignee->getId()));
  }
  
  public function isAssignee(UserProfile $user): bool{
    return $this->assignee !== null && $user->getId()->equals($this->assignee->getId());
  }
  
  public function getEditLevel(?UserProfile $user, ProjectPermissions $perms): int{
    $can_edit = ($user !== null && $this->isAuthorOrAssignee($user)) || $perms->check(ProjectPermissions::EDIT_ALL_ISSUES);
    
    if (!$can_edit){
      return self::EDIT_FORBIDDEN;
    }
    
    $all_fields = ($user !== null && $this->isAssignee($user)) || $perms->check(ProjectPermissions::MODIFY_ALL_ISSUE_FIELDS);
    return $all_fields ? self::EDIT_ALL_FIELDS : self::EDIT_BASIC_FIELDS;
  }
}

?>
