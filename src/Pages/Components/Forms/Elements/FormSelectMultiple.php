<?php
declare(strict_types = 1);

namespace Pages\Components\Forms\Elements;

use Pages\Components\Forms\AbstractFormField;
use Pages\IViewable;

final class FormSelectMultiple extends AbstractFormField{
  private ?string $label;
  private array $options = [];
  
  /**
   * @var string[]
   */
  private array $checked = [];
  
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
  
  public function addOption(string $value, IViewable $label): self{
    $this->options[] = [$value, $label];
    return $this;
  }
  
  public function acceptsMissingField(): bool{
    return true;
  }
  
  /** @noinspection HtmlMissingClosingTag */
  public function echoBody(): void{
    $id = $this->getId();
    $name = $this->getName();
    $name_as_array = $name.'[]';
    $label = $this->label ?? $name;
    
    $disabled_attr = $this->disabled === false ? '' : ' disabled';
    $disabled_class = $this->disabled === false ? '' : ' class="disabled"';
    
    echo '<div class="field-group">';
    $this->echoLabel($label);
    
    echo <<<HTML
<details class="multiselect" id="$id">
  <summary>Select options...</summary>
  <article>
HTML;
    
    foreach($this->options as $option){
      $value = $option[0];
      $value_safe = protect($value);
      $option_label = $option[1];
      
      $option_id = $id.'-'.$value_safe;
      $checked_attr = in_array($value, $this->checked, true) ? ' checked' : '';
      
      echo <<<HTML
    <div class="field-group">
      <input id="$option_id" name="$name_as_array" type="checkbox" value="$value_safe" $checked_attr$disabled_attr><label for="$option_id" $disabled_class>
HTML;
      
      $option_label->echoBody();
      
      echo <<<HTML
      </label>
    </div>
HTML;
    }
    
    echo <<<HTML
  </article>
</details>
HTML;
    
    $this->echoErrors();
    echo '</div>';
  }
}

?>
