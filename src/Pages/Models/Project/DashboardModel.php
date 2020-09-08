<?php
declare(strict_types = 1);

namespace Pages\Models\Project;

use Database\DB;
use Database\Tables\ProjectTable;
use Pages\Components\DashboardWidgetComponent;
use Pages\Components\Markup\LightMarkComponent;
use Pages\Components\Text;
use Pages\Models\BasicProjectPageModel;

/** @noinspection PhpUnused */

class DashboardModel extends BasicProjectPageModel{
  public function getProjectDescription(): ?DashboardWidgetComponent{
    $projects = new ProjectTable(DB::get());
    $description = $projects->getDescription($this->getProject()->getId());
    
    if ($description === null || empty($description)){
      return new DashboardWidgetComponent('Description', 1, Text::missing('Project description is missing.'));
    }
    
    return new DashboardWidgetComponent('Description', 2, new LightMarkComponent($description));
  }
}

?>
