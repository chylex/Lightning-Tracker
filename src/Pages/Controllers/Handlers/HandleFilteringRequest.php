<?php
declare(strict_types = 1);

namespace Pages\Controllers\Handlers;

use Database\Filters\Filtering;
use Pages\Actions\RedirectAction;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Table\Elements\TableFilteringHeaderComponent;
use Pages\Controllers\IControlHandler;
use Pages\IAction;
use Routing\Request;
use Session\Session;

class HandleFilteringRequest implements IControlHandler{
  public function run(Request $req, Session $sess): ?IAction{
    $data = $req->getData();
    $action = $data[FormComponent::ACTION_KEY] ?? '';
    
    if ($action !== TableFilteringHeaderComponent::ACTION_UPDATE){
      return null;
    }
    
    unset($data[FormComponent::ACTION_KEY]);
    
    $rules = [];
    
    foreach($data as $key => $value){
      $pre = Filtering::encode($key).Filtering::KEY_VALUE_SEPARATOR;
      
      if (is_array($value)){
        $rules[] = $pre.implode(Filtering::MULTISELECT_SEPARATOR, array_map(fn($v): string => Filtering::encode($v), $value));
      }
      elseif (!empty($value)){
        $rules[] = $pre.Filtering::encode($value);
      }
    }
    
    $filter_str = implode(Filtering::RULE_SEPARATOR, $rules);
    $patched_url = $req->pathWithGet(Filtering::GET_FILTER, empty($filter_str) ? null : $filter_str);
    
    return new RedirectAction(BASE_URL_ENC.'/'.$patched_url);
  }
}

?>
