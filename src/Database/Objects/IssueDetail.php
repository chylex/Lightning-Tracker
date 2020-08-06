<?php
declare(strict_types = 1);

namespace Database\Objects;

use Pages\Components\Issues\IssuePriority;
use Pages\Components\Issues\IssueScale;
use Pages\Components\Issues\IssueStatus;
use Pages\Components\Issues\IssueType;
use Pages\Components\Markdown\MarkdownComponent;

final class IssueDetail extends IssueInfo{
  private string $description;
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
                              ?string $milestone_title,
                              ?IssueUser $author,
                              ?IssueUser $assignee
  ){
    parent::__construct($id, $title, $type, $priority, $scale, $status, $progress, $date_created, $date_updated);
    $this->description = $description;
    $this->milestone_title = $milestone_title;
    $this->author = $author;
    $this->assignee = $assignee;
  }
  
  public function getDescription(): MarkdownComponent{
    return new MarkdownComponent($this->description);
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
}

?>
