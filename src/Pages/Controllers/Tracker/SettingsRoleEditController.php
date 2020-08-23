<?php
declare(strict_types = 1);

namespace Pages\Controllers\Tracker;

use Database\Objects\TrackerInfo;
use Generator;
use Pages\Controllers\AbstractTrackerController;
use Pages\Controllers\Handlers\LoadNumericId;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\Controllers\Handlers\RequireTrackerPermission;
use Pages\IAction;
use Pages\Models\Tracker\AbstractSettingsModel;
use Pages\Models\Tracker\SettingsRoleEditModel;
use Pages\Views\Tracker\SettingsRoleEditPage;
use Routing\Link;
use Routing\Request;
use Session\Session;
use function Pages\Actions\redirect;
use function Pages\Actions\view;

class SettingsRoleEditController extends AbstractTrackerController{
  private ?int $role_id;
  
  protected function trackerHandlers(TrackerInfo $tracker): Generator{
    yield new RequireLoginState(true);
    yield new RequireTrackerPermission($tracker, AbstractSettingsModel::PERM);
    yield new LoadNumericId($this->role_id, 'role', $tracker);
  }
  
  protected function runTracker(Request $req, Session $sess, TrackerInfo $tracker): IAction{
    $model = new SettingsRoleEditModel($req, $tracker, $this->role_id);
    
    if ($req->getAction() === $model::ACTION_CONFIRM && $model->editRole($req->getData())){
      return redirect(Link::fromBase($req, 'settings', 'roles'));
    }
    
    return view(new SettingsRoleEditPage($model->load()));
  }
}

?>
