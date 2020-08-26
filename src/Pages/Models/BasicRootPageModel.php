<?php
declare(strict_types = 1);

namespace Pages\Models;

use Pages\Components\Navigation\NavigationComponent;
use Pages\Components\Text;
use Pages\Models\Root\SettingsModel;
use Routing\Request;
use Routing\UrlString;
use Session\PermissionManager;
use Session\Permissions\SystemPermissions;

class BasicRootPageModel extends AbstractPageModel{
  public function __construct(Request $req){
    parent::__construct($req);
  }
  
  protected function createNavigation(): NavigationComponent{
    return new NavigationComponent('Lightning Tracker', BASE_URL_ENC, new UrlString(''), $this->getReq()->getRelativePath());
  }
  
  protected function setupNavigation(NavigationComponent $nav, PermissionManager $perms): void{
    $nav->addLeft(Text::withIcon('Trackers', 'book'), '');
    
    $perms = $perms->system();
    
    if ($perms->check(SystemPermissions::LIST_USERS)){
      $nav->addLeft(Text::withIcon('Users', 'users'), '/users');
    }
    
    if ($perms->check(SettingsModel::PERM)){
      $nav->addLeft(Text::withIcon('Settings', 'wrench'), '/settings');
    }
    
    $nav->addLeft(Text::withIcon('About', 'info'), '/about');
  }
}

?>
