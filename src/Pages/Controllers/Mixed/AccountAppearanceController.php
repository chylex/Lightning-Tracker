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
    $data = $req->getData();
    
    if (!empty($data) && $model->updateAppearanceSettings($data)){
      return $model->getAppearanceSettingsForm()->reload($data);
    }
    
    return view(new AccountAppearancePage($model->load()));
  }
}

?>
