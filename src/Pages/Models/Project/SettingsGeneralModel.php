<?php
declare(strict_types = 1);

namespace Pages\Models\Project;

use Database\DB;
use Database\Tables\ProjectTable;
use Database\Validation\ProjectFields;
use Exception;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Text;
use Pages\IModel;
use Validation\FormValidator;
use Validation\ValidationException;

class SettingsGeneralModel extends AbstractSettingsModel{
  public const ACTION_UPDATE = 'Update';
  
  private FormComponent $settings_form;
  
  public function load(): IModel{
    parent::load();
    
    $form = $this->getSettingsForm();
    
    if (!$form->isFilled()){
      $project = $this->getProject();
      $projects = new ProjectTable(DB::get());
      
      $form->fill(['Name'   => $project->getName(),
                   'Hidden' => $projects->isHidden($project->getId())]);
    }
    
    return $this;
  }
  
  public function getSettingsForm(): FormComponent{
    if (isset($this->settings_form)){
      return $this->settings_form;
    }
    
    $form = new FormComponent(self::ACTION_UPDATE);
    $form->startSplitGroup(50);
    $form->addTextField('Name');
    $form->addTextField('Url')->value($this->getProject()->getUrl())->disable();
    $form->endSplitGroup();
    $form->addCheckBox('Hidden')->label('Hidden From Non-Members');
    $form->addButton('submit', 'Update Settings')->icon('pencil');
    
    return $this->settings_form = $form;
  }
  
  public function updateSettings(array $data): bool{
    $form = $this->getSettingsForm();
    
    if (!$form->accept($data)){
      return false;
    }
    
    $validator = new FormValidator($data);
    $name = ProjectFields::name($validator);
    $hidden = ProjectFields::hidden($validator);
    
    try{
      $validator->validate();
      $projects = new ProjectTable(DB::get());
      $projects->changeSettings($this->getProject()->getId(), $name, $hidden);
      $form->addMessage(FormComponent::MESSAGE_SUCCESS, Text::checkmark('Project settings were updated.'));
      return true;
    }catch(ValidationException $e){
      $form->invalidateFields($e->getFields());
    }catch(Exception $e){
      $form->onGeneralError($e);
    }
    
    return false;
  }
}

?>
