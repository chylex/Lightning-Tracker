<?php
declare(strict_types = 1);

namespace Pages\Controllers\Root;

use Pages\Components\Forms\FormComponent;
use Pages\IAction;
use Pages\IController;
use Pages\Models\Root\TrackersModel;
use Pages\Views\Root\TrackersPage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\reload;
use function Pages\Actions\view;

class TrackersController implements IController{
  public function run(Request $req, Session $sess): IAction{
    $model = new TrackersModel($req, $sess->getPermissions());
    $data = $req->getData();
    
    if (!empty($data)){
      $action = $data[FormComponent::ACTION_KEY] ?? '';
      
      if (($action === $model::ACTION_CREATE && $model->createTracker($data, $sess->getLogonUser())) ||
          (($action === $model::ACTION_DELETE && $model->deleteTracker($data)))
      ){
        return reload();
      }
    }
    
    return view(new TrackersPage($model->load()));
  }
}

?>
