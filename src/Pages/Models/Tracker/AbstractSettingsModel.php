<?php
declare(strict_types = 1);

namespace Pages\Models\Tracker;

use Database\Objects\TrackerInfo;
use Pages\Components\Sidemenu\SidemenuComponent;
use Pages\Components\Text;
use Pages\IModel;
use Pages\Models\BasicTrackerPageModel;
use Routing\Request;

class AbstractSettingsModel extends BasicTrackerPageModel{
  private SidemenuComponent $menu_links;
  
  public function __construct(Request $req, TrackerInfo $tracker){
    parent::__construct($req, $tracker);
    
    $this->menu_links = new SidemenuComponent($req);
  }
  
  public function load(): IModel{
    parent::load();
    
    $this->menu_links->addLink(Text::withIcon('General', 'cog'), '/settings');
    $this->menu_links->addLink(Text::withIcon('Roles', 'gavel'), '/settings/roles');
    
    return $this;
  }
  
  public function getMenuLinks(): SidemenuComponent{
    return $this->menu_links;
  }
}

?>
