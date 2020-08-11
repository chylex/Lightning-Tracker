<?php
declare(strict_types = 1);

namespace Pages\Components\Forms\Elements;

use Pages\Components\Forms\AbstractFormField;

final class FormSelectMultiple extends AbstractFormField{
  private string $id;
  private ?string $label;
  
  /**
   * @var string[]
   */
  private array $options = [];
  
  /**
   * @var string[]
   */
  private array $checked = [];
  
  public function __construct(string $id, string $name){
    parent::__construct($name);
    $this->id = $id;
  }
  
  public function label(string $label): self{
    $this->label = $label;
    return $this;
  }
  
  public function value(?string $value): self{
    if ($value === null){
      $this->checked = [];
    }
    else{
      $this->checked = explode(',', $value);
    }
    
    return $this;
  }
  
  public function values(array $values): self{
    $this->checked = $values;
    return $this;
  }
  
  public function addOption(string $value, string $html): self{
    $this->options[] = [$value, $html];
    return $this;
  }
  
  public function acceptsMissingField(): bool{
    return true;
  }
  
  /** @noinspection HtmlMissingClosingTag */
  public function echoBody(): void{
    $name = $this->getName();
    $name_as_array = $name.'[]';
    $label = $this->label ?? $name;
    
    $disabled_attr = $this->disabled === false ? '' : ' disabled';
    $disabled_class = $this->disabled === false ? '' : ' class="disabled"';
    
    echo <<<HTML
<div class="field-group">
  <label for="$this->id"$disabled_class>$label</label>
  <details class="multiselect" id="$this->id">
    <summary>Select options...</summary>
    <article>
HTML;
    
    foreach($this->options as $option){
      $value = $option[0];
      $html = $option[1];
      
      $id = $this->id.'-'.$value;
      $checked_attr = in_array($value, $this->checked, true) ? ' checked' : '';
      
      echo <<<HTML
      <div class="field-group">
        <input id="$id" name="$name_as_array" type="checkbox" value="$value"$checked_attr$disabled_attr>
        <label for="$id"$disabled_class>$html</label>
      </div>
HTML;
    }
    
    echo <<<HTML
    </article>
  </details>
HTML;
    
    $this->echoErrors();
    
    echo <<<HTML
</div>
HTML;
  }
}

?>
