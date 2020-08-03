<?php
declare(strict_types = 1);

namespace Pages\Controllers\Mixed;

use Database\Objects\TrackerInfo;
use Generator;
use Pages\Components\Forms\FormComponent;
use Pages\Controllers\AbstractHandlerController;
use Pages\Controllers\Handlers\OptionallyLoadTracker;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\Controllers\IControlHandler;
use Pages\IAction;
use Pages\Models\Mixed\AccountModel;
use Pages\Views\Mixed\AccountPage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\redirect;
use function Pages\Actions\view;

class AccountController extends AbstractHandlerController{
  protected ?TrackerInfo $tracker;
  
  protected final function prerequisites(): Generator{
    yield new RequireLoginState(true);
    yield new OptionallyLoadTracker($this->tracker);
    
    yield new class implements IControlHandler{
      public function run(Request $req, Session $sess): ?IAction{
        $action = $req->getData()[FormComponent::ACTION_KEY] ?? '';
        
        if ($action === AccountModel::ACTION_LOGOUT){
          $sess->destroyCurrentLogin();
          return redirect([BASE_URL_ENC, $req->getBasePath()->encoded()]);
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
