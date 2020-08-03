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
    $model = new AccountSecurityModel($req, $sess->getLogonUser(), $this->tracker);
    $data = $req->getData();
    
    if (!empty($data) && $model->changePassword($data)){
      return $model->getChangePasswordForm()->reload($data);
    }
    
    return view(new AccountSecurityPage($model->load()));
  }
}

?>
