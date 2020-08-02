<?php
declare(strict_types = 1);

namespace Pages;

use Routing\Request;
use Session\Session;

interface IController{
  public function run(Request $req, Session $sess): IAction;
}

?>
