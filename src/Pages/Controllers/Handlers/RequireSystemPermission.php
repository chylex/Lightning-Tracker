<?php
declare(strict_types = 1);

namespace Pages\Controllers\Handlers;

use Pages\Controllers\IControlHandler;
use Pages\IAction;
use Pages\Models\BasicRootPageModel;
use Pages\Models\ErrorModel;
use Pages\Views\ErrorPage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\view;

class RequireSystemPermission implements IControlHandler{
  private string $permission;
  private string $message;
  
  public function __construct(string $permission, string $message = 'You do not have permission to view this page.'){
    $this->permission = $permission;
    $this->message = $message;
  }
  
  public function run(Request $req, Session $sess): ?IAction{
    if (!$sess->getPermissions()->checkSystem($this->permission)){
      $page_model = new BasicRootPageModel($req);
      $error_model = new ErrorModel($page_model, 'Permission Error', $this->message);
      
      return view(new ErrorPage($error_model->load()));
    }
    
    return null;
  }
}

?>
