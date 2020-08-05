<?php
declare(strict_types = 1);

namespace Pages\Components\Forms;

use Exception;
use Logging\Log;
use Pages\Actions\ReloadFormAction;
use Pages\Components\Forms\Elements\FormButton;
use Pages\Components\Forms\Elements\FormCheckBox;
use Pages\Components\Forms\Elements\FormHiddenValue;
use Pages\Components\Forms\Elements\FormIconButton;
use Pages\Components\Forms\Elements\FormSelect;
use Pages\Components\Forms\Elements\FormSplitGroupEnd;
use Pages\Components\Forms\Elements\FormSplitGroupStart;
use Pages\Components\Forms\Elements\FormTextArea;
use Pages\Components\Forms\Elements\FormTextField;
use Pages\Components\Text;
use Pages\IViewable;
use Validation\InvalidField;
use function Database\protect;

final class FormComponent implements IViewable{
  public const ACTION_KEY = '_Action';
  public const SUB_ACTION_KEY = '_SubAction';
  private const MESSAGES_KEY = '_Messages';
  private const RELOADED_KEY = '_Reloaded';
  
  public const MESSAGE_SUCCESS = 'success';
  public const MESSAGE_ERROR = 'error';
  
  public static function echoHead(): void{
    echo <<<HTML
<link rel="stylesheet" type="text/css" href="~resources/css/forms.css">
HTML;
  }
  
  private string $id;
  private bool $is_filled = false;
  
  private ?string $confirm_message = null;
  
  /**
   * @var IViewable[]
   */
  private array $elements = [];
  
  /**
   * @var IFormField[]
   */
  private array $fields = [];
  
  private array $messages = [];
  
  public function __construct($id = 'Form'){
    $this->id = $id;
  }
  
  public function requireConfirmation(string $message): void{
    $this->confirm_message = $message;
  }
  
  public function addMessage(string $level, Text $text): void{
    $this->messages[] = [$level, $text->getHtml()];
  }
  
  public function startTitledSection(string $title): void{
    $this->elements[] = Text::plain('<h3>'.$title.'</h3><article>');
  }
  
  public function endTitledSection(): void{
    $this->elements[] = Text::plain('</article>');
  }
  
  public function startSplitGroup(int $percentage_per_element, ?string $wrapper_class = null): void{
    $this->elements[] = new FormSplitGroupStart('split-'.$percentage_per_element, $wrapper_class);
  }
  
  public function endSplitGroup(): void{
    $this->elements[] = new FormSplitGroupEnd();
  }
  
  public function addTextField(string $name): FormTextField{
    $field = new FormTextField($this->id.'-'.$name, $name);
    $this->elements[] = $field;
    $this->fields[$name] = $field;
    return $field;
  }
  
  public function addTextArea(string $name): FormTextArea{
    $field = new FormTextArea($this->id.'-'.$name, $name);
    $this->elements[] = $field;
    $this->fields[$name] = $field;
    return $field;
  }
  
  public function addCheckBox(string $name): FormCheckBox{
    $field = new FormCheckBox($this->id.'-'.$name, $name);
    $this->elements[] = $field;
    $this->fields[$name] = $field;
    return $field;
  }
  
  public function addSelect(string $name): FormSelect{
    $field = new FormSelect($this->id.'-'.$name, $name);
    $this->elements[] = $field;
    $this->fields[$name] = $field;
    return $field;
  }
  
  public function addButton(string $type, string $label): FormButton{
    $button = new FormButton($type, $label);
    $this->elements[] = $button;
    return $button;
  }
  
  public function addIconButton(string $type, string $icon): FormIconButton{
    $button = new FormIconButton($type, $icon);
    $this->elements[] = $button;
    return $button;
  }
  
  public function addHidden(string $name, string $value): FormHiddenValue{
    $hidden = new FormHiddenValue($name, $value);
    $this->elements[] = $hidden;
    return $hidden;
  }
  
  public function addHTML(string $html): void{
    $this->elements[] = Text::plain($html);
  }
  
  public function isFilled(): bool{
    return $this->is_filled;
  }
  
  /**
   * Fills form fields using the provided data.
   * @param array $data
   */
  public function fill(array $data){
    $this->is_filled = true;
    
    foreach($this->fields as $name => $field){
      if ($field->isDisabled()){
        continue;
      }
      
      if (isset($data[$name]) || $field->acceptsMissingField()){
        $field->value($data[$name] ?? null);
      }
    }
  }
  
  /**
   * Refills form fields using the provided data, given that the form ID matches.
   * @param array $data
   * @return string|bool Truthy if all fields were present, indicating that the form is ready for validation. The truthy value is the submit button value if present, or true if no button had a set value.
   */
  public function accept(array $data){
    if (!isset($data[self::ACTION_KEY]) || $data[self::ACTION_KEY] !== $this->id){
      return false;
    }
  
    $this->is_filled = true;
    $filled_fields = 0;
    
    foreach($this->fields as $name => $field){
      if ($field->isDisabled()){
        $filled_fields++;
        continue;
      }
      
      if (isset($data[$name]) || $field->acceptsMissingField()){
        $field->value($data[$name] ?? null);
        $filled_fields++;
      }
    }
    
    if ($filled_fields !== count($this->fields)){
      return false;
    }
  
    if (isset($data[self::RELOADED_KEY])){
      if (isset($data[self::MESSAGES_KEY])){
        array_push($this->messages, ...$data[self::MESSAGES_KEY]);
      }
      
      return false; // TODO prevents infinite reloading, but ugly
    }
    
    return $data[self::SUB_ACTION_KEY] ?? true;
  }
  
  /**
   * Reloads the form, saving data and form messages in a session.
   * @param array $data
   * @return ReloadFormAction
   */
  public function reload(array $data): ReloadFormAction{
    $data[self::MESSAGES_KEY] = $this->messages;
    $data[self::RELOADED_KEY] = true;
    return new ReloadFormAction($data);
  }
  
  public function invalidateField(string $name, string $message){
    $this->fields[$name]->addError($message);
  }
  
  /**
   * @param InvalidField[] $invalidFields
   */
  public function invalidateFields(array $invalidFields): void{
    foreach($invalidFields as $invalid_field){
      $name = $invalid_field->getField();
      $this->fields[$name]->addError($invalid_field->getMessage());
    }
  }
  
  public function onGeneralError(Exception $e): void{
    Log::critical($e);
    $this->addMessage(FormComponent::MESSAGE_ERROR, Text::warning('An error occurred while processing your request.'));
  }
  
  public function echoBody(): void{
    $action_key = self::ACTION_KEY;
    $confirm_attr = $this->confirm_message === null ? '' : ' onsubmit="return confirm(\''.protect($this->confirm_message).'\');"';
    
    echo <<<HTML
<form id="$this->id" action="" method="post"$confirm_attr>
HTML;
    
    foreach($this->messages as $message){
      $level = $message[0];
      $text = $message[1];
      echo '<p class="message '.$level.'">'.$text.'</p>';
    }
    
    $groups = [];
    
    foreach($this->elements as $element){
      if ($element instanceof FormSplitGroupEnd){
        array_pop($groups);
      }
      
      foreach($groups as $group){
        echo '<div class="'.$group.'">';
      }
      
      $element->echoBody();
  
      /** @noinspection PhpUnusedLocalVariableInspection */
      foreach($groups as $group){
        echo '</div>';
      }
      
      if ($element instanceof FormSplitGroupStart){
        $groups[] = $element->getSplitClass();
      }
    }
    
    echo <<<HTML
  <input type="hidden" name="$action_key" value="$this->id">
</form>
HTML;
  }
}

?>
