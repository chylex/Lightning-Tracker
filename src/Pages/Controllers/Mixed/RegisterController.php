<?php
declare(strict_types = 1);

namespace Pages\Controllers\Mixed;

use Database\Objects\TrackerInfo;
use Generator;
use Pages\Controllers\AbstractHandlerController;
use Pages\Controllers\Handlers\LoadTracker;
use Pages\IAction;
use Pages\Models\Mixed\RegisterModel;
use Pages\Views\Mixed\RegisterPage;
use Routing\Link;
use Routing\Request;
use Session\Session;
use function Pages\Actions\error;
use function Pages\Actions\redirect;
use function Pages\Actions\view;

class RegisterController extends AbstractHandlerController{
  private ?TrackerInfo $tracker;
  
  protected function prerequisites(): Generator{
    yield (new LoadTracker($this->tracker))->allowMissing();
  }
  
  protected function finally(Request $req, Session $sess): IAction{
    if (isset($_GET['success'])){
      $model = new RegisterModel($req, $this->tracker, true);
      return view(new RegisterPage($model->load()));
    }
    
    if (!SYS_ENABLE_REGISTRATION){
      return error($req, 'Registration Error', 'User registrations are disabled by the administrator.', $this->tracker);
    }
    
    if ($sess->getLogonUser() !== null){
      return redirect(Link::fromBase($req));
    }
    
    $model = new RegisterModel($req, $this->tracker);
    
    if ($req->getAction() === $model::ACTION_REGISTER && $model->registerUser($req->getData(), $sess)){
      return redirect(Link::fromBase($req, 'register?success'));
    }
    
    return view(new RegisterPage($model->load()));
  }
}

?>
