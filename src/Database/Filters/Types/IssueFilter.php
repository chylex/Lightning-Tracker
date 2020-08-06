<?php
declare(strict_types = 1);

namespace Database\Filters\Types;

use Database\Filters\AbstractTrackerIdFilter;

final class IssueFilter extends AbstractTrackerIdFilter{
  public static function empty(): self{
    return new self();
  }
  
  protected function getOrderByColumns(): array{
    return [
        'issue_id' => self::ORDER_DESC
    ];
  }
}

?>
