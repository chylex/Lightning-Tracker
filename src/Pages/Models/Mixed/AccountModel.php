<?php
declare(strict_types = 1);

namespace Pages\Models\Mixed;

use Database\Objects\TrackerInfo;
use Database\Objects\UserProfile;
use Pages\Components\Sidemenu\SidemenuComponent;
use Pages\Components\Text;
use Pages\IModel;
use Pages\Models\BasicMixedPageModel;
use Routing\Request;

class AccountModel extends BasicMixedPageModel{
  public const ACTION_LOGOUT = 'Logout';
  
  private UserProfile $logon_user;
  private SidemenuComponent $menu_links;
  private SidemenuComponent $menu_actions;
  
  public function __construct(Request $req, UserProfile $logon_user, ?TrackerInfo $tracker){
    parent::__construct($req, $tracker);
    $this->logon_user = $logon_user;
    $this->menu_links = new SidemenuComponent($req);
    $this->menu_actions = new SidemenuComponent($req);
  }
  
  public function load(): IModel{
    parent::load();
    
    $this->menu_links->addLink(Text::withIcon('Profile', 'user'), '/account');
    $this->menu_links->addLink(Text::withIcon('Appearance', 'eye'), '/account/appearance');
    $this->menu_links->addLink(Text::withIcon('Security', 'key'), '/account/security');
    
    $this->menu_actions->addActionButton(Text::withIcon('Logout', 'switch'), self::ACTION_LOGOUT);
    
    return $this;
  }
  
  public function getLogonUser(): UserProfile{
    return $this->logon_user;
  }
  
  public function getMenuLinks(): SidemenuComponent{
    return $this->menu_links;
  }
  
  public function getMenuActions(): SidemenuComponent{
    return $this->menu_actions;
  }
}

?>
