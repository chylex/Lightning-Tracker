<?php
declare(strict_types = 1);

namespace Pages\Controllers\Root;

use Generator;
use Pages\Controllers\AbstractHandlerController;
use Pages\Controllers\Handlers\LoadNumericId;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\Controllers\Handlers\RequireSystemPermission;
use Pages\IAction;
use Pages\Models\Root\SettingsRoleEditModel;
use Pages\Views\Root\SettingsRoleEditPage;
use Routing\Link;
use Routing\Request;
use Session\Permissions\SystemPermissions;
use Session\Session;
use function Pages\Actions\redirect;
use function Pages\Actions\view;

class SettingsRoleEditController extends AbstractHandlerController{
  private ?int $role_id;
  
  protected function prerequisites(): Generator{
    yield new RequireLoginState(true);
    yield new RequireSystemPermission(SystemPermissions::MANAGE_SETTINGS);
    yield new LoadNumericId($this->role_id, 'role');
  }
  
  protected function finally(Request $req, Session $sess): IAction{
    $model = new SettingsRoleEditModel($req, $this->role_id);
    
    if ($req->getAction() === $model::ACTION_CONFIRM && $model->editRole($req->getData())){
      return redirect(Link::fromBase($req, 'settings', 'roles'));
    }
    
    return view(new SettingsRoleEditPage($model->load()));
  }
}

?>
