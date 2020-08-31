<?php
declare(strict_types = 1);

namespace Pages\Models\Mixed;

use Database\Objects\ProjectInfo;
use Database\Objects\UserProfile;
use Pages\Components\Sidemenu\SidemenuComponent;
use Pages\Components\Text;
use Pages\Models\BasicMixedPageModel;
use Routing\Request;

class AccountModel extends BasicMixedPageModel{
  public const ACTION_LOGOUT = 'Logout';
  
  private UserProfile $user;
  
  public function __construct(Request $req, UserProfile $user, ?ProjectInfo $project){
    parent::__construct($req, $project);
    $this->user = $user;
  }
  
  public function getUser(): UserProfile{
    return $this->user;
  }
  
  public final function createMenuLinks(): SidemenuComponent{
    $menu = new SidemenuComponent($this->getReq());
    $menu->addLink(Text::withIcon('Profile', 'user'), '/account');
    $menu->addLink(Text::withIcon('Appearance', 'eye'), '/account/appearance');
    $menu->addLink(Text::withIcon('Security', 'key'), '/account/security');
    return $menu;
  }
  
  public final function createMenuActions(): SidemenuComponent{
    $menu = new SidemenuComponent($this->getReq());
    $menu->addActionButton(Text::withIcon('Logout', 'switch'), self::ACTION_LOGOUT);
    return $menu;
  }
}

?>
