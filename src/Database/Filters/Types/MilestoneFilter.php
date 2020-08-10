<?php
declare(strict_types = 1);

namespace Database\Filters\Types;

use Database\Filters\AbstractTrackerIdFilter;
use Database\Filters\Sorting;

final class MilestoneFilter extends AbstractTrackerIdFilter{
  public static function empty(): self{
    return new self();
  }
  
  protected function getDefaultOrderByColumns(): array{
    return [
        'm.ordering' => Sorting::SQL_ASC
    ];
  }
}

?>
