<?php
declare(strict_types = 1);

namespace Database\Filters\Types;

use Database\Filters\AbstractTrackerIdFilter;
use Database\Filters\General\Sorting;

final class IssueFilter extends AbstractTrackerIdFilter{
  public static function empty(): self{
    return new self();
  }
  
  protected function getSortingColumns(): array{
    return [
        'title',
        'priority',
        'scale',
        'progress'
    ];
  }
  
  protected function getDefaultOrderByColumns(): array{
    return [
        'issue_id' => Sorting::SQL_DESC
    ];
  }
}

?>
