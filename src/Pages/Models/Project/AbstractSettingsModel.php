<?php
declare(strict_types = 1);

namespace Pages\Models\Project;

use Pages\Components\Sidemenu\SidemenuComponent;
use Pages\Components\Text;
use Pages\Models\BasicProjectPageModel;

class AbstractSettingsModel extends BasicProjectPageModel{
  public function createMenuLinks(): SidemenuComponent{
    $menu = new SidemenuComponent($this->getReq());
    $menu->addLink(Text::withIcon('General', 'cog'), '/settings');
    $menu->addLink(Text::withIcon('Description', 'pencil'), '/settings/description');
    $menu->addLink(Text::withIcon('Roles', 'gavel'), '/settings/roles');
    return $menu;
  }
}

?>
