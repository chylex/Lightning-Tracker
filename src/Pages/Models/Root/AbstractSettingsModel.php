<?php
declare(strict_types = 1);

namespace Pages\Models\Root;

use Pages\Components\Sidemenu\SidemenuComponent;
use Pages\Components\Text;
use Pages\Models\BasicRootPageModel;

class AbstractSettingsModel extends BasicRootPageModel{
  public function createMenuLinks(): SidemenuComponent{
    $menu = new SidemenuComponent($this->getReq());
    $menu->addLink(Text::withIcon('General', 'cog'), '/settings');
    $menu->addLink(Text::withIcon('Roles', 'gavel'), '/settings/roles');
    return $menu;
  }
}

?>
