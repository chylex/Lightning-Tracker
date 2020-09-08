<?php
declare(strict_types = 1);

namespace Pages\Components\Forms;

use Exception;
use Logging\Log;
use Pages\Actions\ReloadFormAction;
use Pages\Components\Forms\Elements\FormButton;
use Pages\Components\Forms\Elements\FormCheckBox;
use Pages\Components\Forms\Elements\FormCheckBoxHierarchyItem;
use Pages\Components\Forms\Elements\FormHiddenValue;
use Pages\Components\Forms\Elements\FormIconButton;
use Pages\Components\Forms\Elements\FormLightMarkEditor;
use Pages\Components\Forms\Elements\FormMessageList;
use Pages\Components\Forms\Elements\FormNumberField;
use Pages\Components\Forms\Elements\FormSelect;
use Pages\Components\Forms\Elements\FormSelectMultiple;
use Pages\Components\Forms\Elements\FormSplitGroupEnd;
use Pages\Components\Forms\Elements\FormSplitGroupStart;
use Pages\Components\Forms\Elements\FormTextField;
use Pages\Components\Html;
use Pages\Components\Text;
use Pages\IViewable;
use Routing\Request;
use Validation\InvalidField;

final class FormComponent implements IViewable{
  public const ACTION_KEY = '_Action';
  public const BUTTON_KEY = '_Button';
  private const MESSAGES_KEY = '_Messages';
  private const RELOADED_KEY = '_Reloaded';
  
  public const MESSAGE_SUCCESS = 'success';
  public const MESSAGE_ERROR = 'error';
  
  private static $global_counter = 0;
  
  public static function echoHead(): void{
    if (DEBUG){
      echo '<link rel="stylesheet" type="text/css" href="~resources/css/forms.css?v='.TRACKER_RESOURCE_VERSION.'">';
    }
    
    echo '<script type="text/javascript" src="~resources/js/forms.js?v='.TRACKER_RESOURCE_VERSION.'"></script>';
  }
  
  private string $id;
  private string $action;
  private bool $is_filled = false;
  
  private ?string $confirm_message = null;
  
  /**
   * @var IViewable[]
   */
  private array $elements;
  private FormMessageList $message_list;
  
  /**
   * @var IFormField[]
   */
  private array $fields = [];
  
  public function __construct(string $action){
    $this->id = $action.'-'.(++self::$global_counter);
    $this->action = $action;
    
    $this->message_list = new FormMessageList();
    $this->elements = [$this->message_list];
  }
  
  public function requireConfirmation(string $message): void{
    $this->confirm_message = $message;
  }
  
  public function addMessage(string $level, Text $text): void{
    $this->message_list->addMessage($level, $text);
  }
  
  public function setMessagePlacementHere(): void{
    $key = array_search($this->message_list, $this->elements, true);
    
    if ($key !== false){
      unset($this->elements[$key]);
    }
    
    $this->elements[] = $this->message_list;
  }
  
  public function startTitledSection(string $title): void{
    $this->elements[] = new Html('<h3>'.$title.'</h3><article>');
  }
  
  public function endTitledSection(): void{
    $this->elements[] = new Html('</article>');
  }
  
  public function startSplitGroup(int $percentage_per_element, ?string $wrapper_class = null): void{
    $this->elements[] = new FormSplitGroupStart('split-'.$percentage_per_element, $wrapper_class);
  }
  
  public function endSplitGroup(): void{
    $this->elements[] = new FormSplitGroupEnd();
  }
  
  public function startCheckBoxHierarchy(string $title): void{
    $this->elements[] = new Html('<label>'.$title.'</label><div class="field-group">');
  }
  
  public function addCheckBoxHierarchyItem(string $name): FormCheckBoxHierarchyItem{
    $field = new FormCheckBoxHierarchyItem($this->id.'-'.$name, $name);
    $this->elements[] = $field;
    $this->fields[$name] = $field;
    return $field;
  }
  
  public function endCheckBoxHierarchy(): void{
    $this->elements[] = new Html('</div>');
  }
  
  public function addTextField(string $name): FormTextField{
    $field = new FormTextField($this->id.'-'.$name, $name);
    $this->elements[] = $field;
    $this->fields[$name] = $field;
    return $field;
  }
  
  public function addNumberField(string $name, int $min, int $max): FormNumberField{
    $field = new FormNumberField($this->id.'-'.$name, $name, $min, $max);
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
  
  public function addSelectMultiple(string $name): FormSelectMultiple{
    $field = new FormSelectMultiple($this->id.'-'.$name, $name);
    $this->elements[] = $field;
    $this->fields[$name] = $field;
    return $field;
  }
  
  public function addLightMarkEditor(string $name): FormLightMarkEditor{
    $field = new FormLightMarkEditor($this->id.'-'.$name, $name);
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
    $this->elements[] = new Html($html);
  }
  
  public function isFilled(): bool{
    return $this->is_filled;
  }
  
  /**
   * Fills form fields using the provided data.
   *
   * @param array $data
   */
  public function fill(array $data): void{
    $this->is_filled = true;
    
    foreach($this->fields as $name => $field){
      if (isset($data[$name]) || $field->acceptsMissingField()){
        $field->value($data[$name] ?? null);
      }
    }
  }
  
  public function disableAllFields(): void{
    foreach($this->fields as $field){
      $field->disable();
    }
  }
  
  /**
   * Refills form fields using the provided data, given that the form ID matches.
   *
   * @param array $data
   * @return bool True if all fields were present, indicating that the form is ready for validation.
   */
  public function accept(array $data): bool{
    if (!isset($data[self::ACTION_KEY]) || $data[self::ACTION_KEY] !== $this->action){
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
        $this->message_list->appendMessages($data[self::MESSAGES_KEY]);
      }
      
      return false; // TODO prevents infinite reloading, but ugly
    }
    
    return true;
  }
  
  /**
   * Reloads the form, saving data and form messages in a session.
   *
   * @param Request $req
   * @return ReloadFormAction
   */
  public function reload(Request $req): ReloadFormAction{
    $data = $req->getData();
    $data[self::MESSAGES_KEY] = $this->message_list->getMessages();
    $data[self::RELOADED_KEY] = true;
    return new ReloadFormAction($data);
  }
  
  public function invalidateField(string $name, string $message): void{
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
    $this->addMessage(self::MESSAGE_ERROR, Text::blocked('An error occurred while processing your request.'));
  }
  
  public function echoBody(): void{
    $action_key = self::ACTION_KEY;
    $confirm_attr = $this->confirm_message === null ? '' : ' onsubmit="return confirm(\''.protect($this->confirm_message).'\');"';
    
    echo <<<HTML
<form id="$this->id" action="" method="post" $confirm_attr>
HTML;
    
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
  <input type="hidden" name="$action_key" value="$this->action">
</form>
HTML;
  }
}

?>
