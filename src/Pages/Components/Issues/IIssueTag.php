<?php
declare(strict_types = 1);

namespace Pages\Components\Issues;

interface IIssueTag{
  public function getId(): string;
  public function getTitle(): string;
  public function getTagClass(): string;
}

?>
