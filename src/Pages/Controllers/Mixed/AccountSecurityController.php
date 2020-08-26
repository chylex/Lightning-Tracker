<?php
declare(strict_types = 1);

namespace Pages\Controllers\Mixed;

use Pages\IAction;
use Pages\Models\Mixed\AccountSecurityModel;
use Pages\Views\Mixed\AccountSecurityPage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\view;

class AccountSecurityController extends AccountController{
  protected function finally(Request $req, Session $sess): IAction{
    $model = new AccountSecurityModel($req, $sess->getLogonUser(), $this->project);
    
    if ($req->getAction() === $model::ACTION_CHANGE_PASSWORD && $model->changePassword($req->getData())){
      return $model->getChangePasswordForm()->reload($req);
    }
    
    return view(new AccountSecurityPage($model->load()));
  }
}

?>
