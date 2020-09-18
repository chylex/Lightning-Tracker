<?php
declare(strict_types = 1);

namespace Data;

final class CreateOrEditIssue{
  public static function create(?string $type = null): self{
    return new CreateOrEditIssue(null, $type);
  }
  
  public static function edit(int $issue_id): self{
    return new CreateOrEditIssue($issue_id, null);
  }
  
  private ?int $issue_id;
  private ?string $new_issue_type;
  
  private function __construct(?int $issue_id, ?string $new_issue_type){
    $this->issue_id = $issue_id;
    $this->new_issue_type = $new_issue_type;
  }
  
  public function isNewIssue(): bool{
    return $this->issue_id === null;
  }
  
  public function getNewIssueType(): ?string{
    return $this->new_issue_type;
  }
  
  public function getIssueId(): ?int{
    return $this->issue_id;
  }
}

?>
