<?php
declare(strict_types = 1);

namespace Pages\Controllers\Mixed;

use Database\Objects\TrackerInfo;
use Generator;
use Pages\Controllers\AbstractHandlerController;
use Pages\Controllers\Handlers\LoadTracker;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\Controllers\IControlHandler;
use Pages\IAction;
use Pages\Models\Mixed\AccountModel;
use Pages\Views\Mixed\AccountPage;
use Routing\Link;
use Routing\Request;
use Session\Session;
use function Pages\Actions\redirect;
use function Pages\Actions\view;

class AccountController extends AbstractHandlerController{
  protected ?TrackerInfo $tracker;
  
  protected final function prerequisites(): Generator{
    yield new RequireLoginState(true);
    yield (new LoadTracker($this->tracker))->allowMissing();
    
    yield new class implements IControlHandler{
      public function run(Request $req, Session $sess): ?IAction{
        if ($req->getAction() === AccountModel::ACTION_LOGOUT){
          $sess->destroyCurrentLogin();
          return redirect(Link::fromBase($req));
        }
        
        return null;
      }
    };
  }
  
  protected function finally(Request $req, Session $sess): IAction{
    return view(new AccountPage((new AccountModel($req, $sess->getLogonUser(), $this->tracker))->load()));
  }
}

?>
