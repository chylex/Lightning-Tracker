<?php
declare(strict_types = 1);

namespace Pages\Controllers\Handlers;

use Database\Filters\General\Filtering;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Table\Elements\TableFilteringHeaderComponent;
use Pages\Controllers\IControlHandler;
use Pages\IAction;
use Routing\Link;
use Routing\Request;
use Session\Session;
use function Pages\Actions\redirect;

class HandleFilteringRequest implements IControlHandler{
  public function run(Request $req, Session $sess): ?IAction{
    $data = $req->getData();
    $action = $data[FormComponent::ACTION_KEY] ?? '';
    
    if ($action !== TableFilteringHeaderComponent::ACTION_UPDATE){
      return null;
    }
    
    $button = $data[FormComponent::BUTTON_KEY] ?? TableFilteringHeaderComponent::BUTTON_SET;
    
    unset($data[FormComponent::ACTION_KEY]);
    unset($data[FormComponent::BUTTON_KEY]);
    
    $rules = [];
    
    if ($button === TableFilteringHeaderComponent::BUTTON_SET){
      foreach($data as $key => $value){
        $pre = Filtering::encode($key).Filtering::KEY_VALUE_SEPARATOR;
        
        if (is_array($value)){
          $rules[] = $pre.implode(Filtering::MULTISELECT_SEPARATOR, array_map(static fn($v): string => Filtering::encode($v), $value));
        }
        elseif (!empty($value)){
          $rules[] = $pre.Filtering::encode($value);
        }
      }
      
      $filter_str = implode(Filtering::RULE_SEPARATOR, $rules);
      $patched_url = Link::withGet($req, Filtering::GET_FILTER, empty($filter_str) ? null : $filter_str);
    }
    else{
      $patched_url = Link::withGet($req, Filtering::GET_FILTER, null);
    }
    
    return redirect($patched_url);
  }
}

?>
