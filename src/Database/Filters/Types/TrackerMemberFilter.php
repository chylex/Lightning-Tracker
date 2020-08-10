<?php
declare(strict_types = 1);

namespace Database\Filters\Types;

use Database\Filters\AbstractTrackerIdFilter;
use Database\Filters\Sorting;

final class TrackerMemberFilter extends AbstractTrackerIdFilter{
  public static function empty(): self{
    return new self();
  }
  
  protected function getSortingColumns(): array{
    return [
        'name',
        'role_title'
    ];
  }
  
  protected function getDefaultOrderByColumns(): array{
    return [
        'role_order' => Sorting::SQL_ASC,
        'user_id'    => Sorting::SQL_DESC
    ];
  }
}

?>
