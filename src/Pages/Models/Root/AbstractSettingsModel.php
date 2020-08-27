<?php
declare(strict_types = 1);

namespace Pages\Models\Root;

use Pages\Components\Sidemenu\SidemenuComponent;
use Pages\Components\Text;
use Pages\IModel;
use Pages\Models\BasicRootPageModel;
use Routing\Request;

class AbstractSettingsModel extends BasicRootPageModel{
  private SidemenuComponent $menu_links;
  
  public function __construct(Request $req){
    parent::__construct($req);
    
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
