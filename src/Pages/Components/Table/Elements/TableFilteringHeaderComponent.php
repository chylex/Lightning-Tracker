<?php
declare(strict_types = 1);

namespace Pages\Components\Table\Elements;

use Database\Filters\General\Filtering;
use LogicException;
use Pages\Components\Forms\Elements\FormSelectMultiple;
use Pages\Components\Forms\Elements\FormTextField;
use Pages\Components\Forms\FormComponent;
use Pages\IViewable;

final class TableFilteringHeaderComponent implements IViewable{
  public const ACTION_UPDATE = 'UpdateTableFilter';
  
  private Filtering $filtering;
  private string $id;
  
  /**
   * @var IViewable[]
   */
  private array $fields = [];
  
  private int $active_filters = 0;
  
  public function __construct(Filtering $filtering, string $id = 'Filter'){
    $this->filtering = $filtering;
    $this->id = $id;
  }
  
  /**
   * @param string $field
   * @param int $type
   * @return mixed
   */
  private function getFilterValue(string $field, int $type){
    if (!$this->filtering->isFilterable($field)){
      throw new LogicException('Attempted to filter a non-filterable field.');
    }
    
    $value = $this->filtering->getFilter($field, $type);
    
    if ($value){ // TODO ensure the filter does not have invalid values?
      ++$this->active_filters;
    }
    
    return $value;
  }
  
  public function addTextField(string $name): FormTextField{
    $field = new FormTextField($this->id.'-'.$name, $name);
    $field->value($this->getFilterValue($name, Filtering::TYPE_TEXT) ?? '');
    $this->fields[$name] = $field;
    return $field;
  }
  
  public function addMultiSelect(string $name): FormSelectMultiple{
    $field = new FormSelectMultiple($this->id.'-'.$name, $name);
    $field->values($this->getFilterValue($name, Filtering::TYPE_MULTISELECT) ?? []);
    $this->fields[$name] = $field;
    return $field;
  }
  
  /** @noinspection HtmlMissingClosingTag */
  public function echoBody(): void{
    $action_key = FormComponent::ACTION_KEY;
    $action_value = self::ACTION_UPDATE;
    
    $active_str = $this->active_filters === 0 ? '' : ' ('.$this->active_filters.' active)';
    
    echo <<<HTML
<form action="" method="post">
  <input type="hidden" name="$action_key" value="$action_value">
  <details class="filtering">
    <summary>
      <span class="icon icon-filter"></span>
      <span class="hide-if-open">Show</span>
      <span class="show-if-open">Hide</span>
      Filters$active_str
    </summary>
    <article>
HTML;
    
    foreach($this->fields as $field){
      $field->echoBody();
    }
    
    echo <<<HTML
      <div class="buttons">
        <button class="styled" type="submit"><span class="icon icon-checkmark"></span></button>
        <button class="styled" type="reset"><span class="icon icon-blocked"></span></button>
      </div>
    </article>
  </details>
</form>
HTML;
  }
}

?>
