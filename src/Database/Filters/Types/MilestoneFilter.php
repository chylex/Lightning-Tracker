<?php
declare(strict_types = 1);

namespace Database\Filters\Types;

use Database\Filters\AbstractTrackerIdFilter;

final class MilestoneFilter extends AbstractTrackerIdFilter{
  public static function empty(): self{
    return new self();
  }
  
  protected function getOrderByColumns(): array{
    return [
        'ordering' => self::ORDER_ASC
    ];
  }
}

?>
