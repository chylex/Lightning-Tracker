<?php
declare(strict_types = 1);

namespace Database\Filters\Types;

use Database\Filters\AbstractTrackerIdFilter;

final class TrackerMemberFilter extends AbstractTrackerIdFilter{
  public static function empty(): self{
    return new self();
  }
  
  protected function getOrderByColumns(): array{
    return [
        'role_order' => self::ORDER_ASC,
        'user_id'    => self::ORDER_DESC
    ];
  }
}

?>
