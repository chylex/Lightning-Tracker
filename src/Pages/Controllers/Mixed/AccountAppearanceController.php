<?php
declare(strict_types = 1);

namespace Pages\Controllers\Mixed;

use Pages\IAction;
use Pages\Models\Mixed\AccountAppearanceModel;
use Pages\Views\Mixed\AccountAppearancePage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\view;

class AccountAppearanceController extends AccountController{
  protected function finally(Request $req, Session $sess): IAction{
    $model = new AccountAppearanceModel($req, $sess->getLogonUser(), $this->tracker);
    
    if ($req->getAction() === $model::ACTION_CHANGE_APPEARANCE && $model->updateAppearanceSettings($req->getData())){
      return $model->getAppearanceSettingsForm()->reload($req);
    }
    
    return view(new AccountAppearancePage($model->load()));
  }
}

?>
