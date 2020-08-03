<?php
declare(strict_types = 1);

namespace Pages\Models;

use Pages\Components\Navigation\NavigationComponent;
use Pages\Components\Text;
use Routing\Request;
use Session\Permissions;

class BasicRootPageModel extends AbstractPageModel{
  public function __construct(Request $req){
    parent::__construct($req);
  }
  
  protected function createNavigation(): NavigationComponent{
    return new NavigationComponent('Lightning Tracker', BASE_URL_ENC, $this->getReq());
  }
  
  protected function setupNavigation(NavigationComponent $nav, Permissions $perms): void{
    $nav->addLeft(Text::withIcon('About', 'info'), '/about');
  }
}

?>
