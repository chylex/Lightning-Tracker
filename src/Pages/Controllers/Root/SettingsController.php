<?php
declare(strict_types = 1);

namespace Pages\Controllers\Root;

use Generator;
use Pages\Controllers\AbstractHandlerController;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\Controllers\Handlers\RequireSystemPermission;
use Pages\IAction;
use Pages\Models\Root\SettingsModel;
use Pages\Views\Root\SettingsPage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\view;

class SettingsController extends AbstractHandlerController{
  protected function prerequisites(): Generator{
    yield new RequireLoginState(true);
    yield new RequireSystemPermission(SettingsModel::PERM);
  }
  
  protected function finally(Request $req, Session $sess): IAction{
    $model = new SettingsModel($req);
    $data = $req->getData();
  
    if (!empty($data)){
      $form = $model->getForm();
      $action = $form->accept($data);
    
      if (($action === $model::ACTION_REMOVE_BACKUP && $model->removeBackupFile()) ||
          ($action === $model::ACTION_UPDATE_SETTINGS && $model->updateConfig($data))
      ){
        return $form->reload($data);
      }
    }
  
    return view(new SettingsPage($model->load()));
  }
}

?>
