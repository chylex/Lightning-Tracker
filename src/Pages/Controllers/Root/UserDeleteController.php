<?php
declare(strict_types = 1);

namespace Pages\Controllers\Root;

use Data\UserId;
use Generator;
use Pages\Controllers\AbstractHandlerController;
use Pages\Controllers\Handlers\LoadStringId;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\Controllers\Handlers\RequireSystemPermission;
use Pages\IAction;
use Pages\Models\Root\UserDeleteModel;
use Pages\Views\Root\UserDeletePage;
use Routing\Link;
use Routing\Request;
use Session\Permissions\SystemPermissions;
use Session\Session;
use function Pages\Actions\message;
use function Pages\Actions\redirect;
use function Pages\Actions\view;

class UserDeleteController extends AbstractHandlerController{
  private ?string $user_id;
  
  protected function prerequisites(): Generator{
    yield new RequireLoginState(true);
    yield new RequireSystemPermission(SystemPermissions::LIST_USERS);
    yield new RequireSystemPermission(SystemPermissions::MANAGE_USERS);
    yield new LoadStringId($this->user_id, 'user');
  }
  
  protected function finally(Request $req, Session $sess): IAction{
    $model = new UserDeleteModel($req, UserId::fromFormatted($this->user_id), $sess->getLogonUserIdOrThrow());
    
    if ($model->getUser() !== null){
      if (!$model->canDelete()){
        return message($req, 'Permission Error', 'You are not allowed to delete this user.');
      }
      
      if ($req->getAction() === $model::ACTION_CONFIRM && $model->deleteUser($req->getData())){
        return redirect(Link::fromBase($req, 'users'));
      }
    }
    
    return view(new UserDeletePage($model->load()));
  }
}

?>
