<?php
declare(strict_types = 1);

namespace Database\Objects;

use Pages\Components\Issues\IssuePriority;
use Pages\Components\Issues\IssueScale;
use Pages\Components\Issues\IssueStatus;
use Pages\Components\Issues\IssueType;
use function Database\protect;

class IssueInfo{
  private int $id;
  private string $title;
  private IssueType $type;
  private IssuePriority $priority;
  private IssueScale $scale;
  private IssueStatus $status;
  private int $progress;
  private string $date_created;
  private string $date_updated;
  
  public function __construct(int $id,
                              string $title,
                              IssueType $type,
                              IssuePriority $priority,
                              IssueScale $scale,
                              IssueStatus $status,
                              int $progress,
                              string $date_created,
                              string $date_updated
  ){
    $this->id = $id;
    $this->title = $title;
    $this->type = $type;
    $this->priority = $priority;
    $this->scale = $scale;
    $this->status = $status;
    $this->progress = $progress;
    $this->date_created = $date_created;
    $this->date_updated = $date_updated;
  }
  
  public final function getId(): int{
    return $this->id;
  }
  
  public final function getTitleSafe(): string{
    return protect($this->title);
  }
  
  public final function getType(): IssueType{
    return $this->type;
  }
  
  public final function getPriority(): IssuePriority{
    return $this->priority;
  }
  
  public final function getScale(): IssueScale{
    return $this->scale;
  }
  
  public final function getStatus(): IssueStatus{
    return $this->status;
  }
  
  public final function getProgress(): int{
    return $this->progress;
  }
  
  public final function getCreationDate(): string{
    return $this->date_created;
  }
  
  public final function getLastUpdateDate(): string{
    return $this->date_updated;
  }
}

?>
