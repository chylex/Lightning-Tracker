<?php
declare(strict_types = 1);

namespace Pages\Controllers\Root;

use Pages\IAction;
use Pages\IController;
use Pages\Models\BasicRootPageModel;
use Pages\Views\Root\AboutPage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\view;

class AboutController implements IController{
  public function run(Request $req, Session $sess): IAction{
    return view(new AboutPage((new BasicRootPageModel($req))->load()));
  }
}

?>
