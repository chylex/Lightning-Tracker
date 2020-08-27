<?php
declare(strict_types = 1);

namespace Pages\Controllers\Root;

use Generator;
use Pages\Components\Forms\FormComponent;
use Pages\Controllers\AbstractHandlerController;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\Controllers\Handlers\RequireSystemPermission;
use Pages\IAction;
use Pages\Models\Root\SettingsGeneralModel;
use Pages\Views\Root\SettingsGeneralPage;
use Routing\Request;
use Session\Permissions\SystemPermissions;
use Session\Session;
use function Pages\Actions\view;

class SettingsGeneralController extends AbstractHandlerController{
  protected function prerequisites(): Generator{
    yield new RequireLoginState(true);
    yield new RequireSystemPermission(SystemPermissions::MANAGE_SETTINGS);
  }
  
  protected function finally(Request $req, Session $sess): IAction{
    $model = new SettingsGeneralModel($req);
    
    if ($req->getAction() === $model::ACTION_SUBMIT){
      $data = $req->getData();
      $button = $data[FormComponent::BUTTON_KEY] ?? null;
      
      if (($button === $model::BUTTON_REMOVE_BACKUP && $model->removeBackupFile($data)) ||
          ($button === $model::BUTTON_UPDATE_SETTINGS && $model->updateConfig($data))
      ){
        return $model->getForm()->reload($req);
      }
    }
    
    return view(new SettingsGeneralPage($model->load()));
  }
}

?>
