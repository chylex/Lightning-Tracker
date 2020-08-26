<?php
declare(strict_types = 1);

namespace Pages\Actions;

use Database\Objects\ProjectInfo;
use Pages\Models\BasicMixedPageModel;
use Pages\Models\ErrorModel;
use Pages\Views\AbstractPage;
use Pages\Views\ErrorPage;
use Routing\Request;

function view(AbstractPage $view): ViewAction{
  return new ViewAction($view);
}

function error(Request $req, string $title, string $message, ?ProjectInfo $project = null): ViewAction{
  return view(new ErrorPage((new ErrorModel(new BasicMixedPageModel($req, $project), $title, $message))->load()));
}

function redirect(string $url): RedirectAction{
  return new RedirectAction($url);
}

function reload(): RedirectAction{
  return new RedirectAction($_SERVER['REQUEST_URI']);
}

function json(array $data): JsonAction{
  return new JsonAction($data);
}

?>
