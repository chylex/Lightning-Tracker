<?php
declare(strict_types = 1);

namespace Pages\Controllers\Root;

use Generator;
use Pages\Controllers\AbstractHandlerController;
use Pages\Controllers\Handlers\HandleFilteringRequest;
use Pages\IAction;
use Pages\Models\Root\TrackersModel;
use Pages\Views\Root\TrackersPage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\reload;
use function Pages\Actions\view;

class TrackersController extends AbstractHandlerController{
  protected function prerequisites(): Generator{
    yield new HandleFilteringRequest();
  }
  
  protected function finally(Request $req, Session $sess): IAction{
    $model = new TrackersModel($req, $sess->getPermissions());
    
    if ($req->getAction() === $model::ACTION_CREATE && $model->createTracker($req->getData(), $sess->getLogonUser())){
      return reload();
    }
    
    return view(new TrackersPage($model->load()));
  }
}

?>
