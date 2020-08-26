<?php
declare(strict_types = 1);

namespace Database;

use Database\Filters\AbstractProjectIdFilter;
use Database\Objects\ProjectInfo;
use PDO;

abstract class AbstractProjectTable extends AbstractTable{
  private int $project_id;
  
  public function __construct(PDO $db, ProjectInfo $project){
    parent::__construct($db);
    $this->project_id = $project->getId();
  }
  
  protected function getProjectId(): int{
    return $this->project_id;
  }
  
  protected function prepareFilter(AbstractProjectIdFilter $filter, ?string $table_name = null): AbstractProjectIdFilter{
    return $filter->internalSetProject($this->getProjectId(), $table_name);
  }
}

?>
