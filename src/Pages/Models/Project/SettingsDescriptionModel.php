<?php
declare(strict_types = 1);

namespace Pages\Models\Project;

use Database\DB;
use Database\Objects\ProjectInfo;
use Database\Tables\ProjectTable;
use Database\Validation\ProjectFields;
use Exception;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Text;
use Pages\IModel;
use Routing\Request;
use Session\Permissions\ProjectPermissions;
use Validation\FormValidator;
use Validation\ValidationException;

class SettingsDescriptionModel extends AbstractSettingsModel{
  public const ACTION_UPDATE = 'Update';
  
  private ProjectPermissions $perms;
  
  private FormComponent $settings_form;
  
  public function __construct(Request $req, ProjectInfo $project, ProjectPermissions $perms){
    parent::__construct($req, $project);
    $this->perms = $perms;
  }
  
  public function load(): IModel{
    parent::load();
    
    $form = $this->getEditDescriptionForm();
    
    if (!$form->isFilled()){
      $form->fill(['Description' => (new ProjectTable(DB::get()))->getDescription($this->getProject()->getId())]);
    }
    
    return $this;
  }
  
  public function getEditDescriptionForm(): FormComponent{
    if (isset($this->settings_form)){
      return $this->settings_form;
    }
    
    $form = new FormComponent(self::ACTION_UPDATE);
    $form->addLightMarkEditor('Description')->label('');
    
    if ($this->perms->check(ProjectPermissions::MANAGE_SETTINGS_DESCRIPTION)){
      $form->addButton('submit', 'Update Description')->icon('pencil');
    }
    else{
      $form->disableAllFields();
    }
    
    return $this->settings_form = $form;
  }
  
  public function updateDescription(array $data): bool{
    $form = $this->getEditDescriptionForm();
    
    if (!$form->accept($data)){
      return false;
    }
    
    $validator = new FormValidator($data);
    $description = ProjectFields::description($validator);
    
    try{
      $validator->validate();
      $projects = new ProjectTable(DB::get());
      $projects->setDescription($this->getProject()->getId(), $description);
      $form->addMessage(FormComponent::MESSAGE_SUCCESS, Text::checkmark('Project description was updated.'));
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
