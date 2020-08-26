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
use Validation\FormValidator;
use Validation\ValidationException;

class SettingsGeneralModel extends AbstractSettingsModel{
  public const ACTION_UPDATE = 'Update';
  
  private FormComponent $form;
  
  public function __construct(Request $req, ProjectInfo $project){
    parent::__construct($req, $project);
    
    $this->form = new FormComponent(self::ACTION_UPDATE);
    $this->form->startTitledSection('Project');
    $this->form->setMessagePlacementHere();
    
    $this->form->startSplitGroup(50);
    $this->form->addTextField('Name');
    $this->form->addTextField('Url')->value($project->getUrl())->disable();
    $this->form->endSplitGroup();
    
    $this->form->addCheckBox('Hidden')->label('Hidden From Non-Members');
    $this->form->addButton('submit', 'Update Settings')->icon('pencil');
    
    $this->form->endTitledSection();
  }
  
  public function load(): IModel{
    parent::load();
    
    if (!$this->form->isFilled()){
      $project = $this->getProject();
      $projects = new ProjectTable(DB::get());
      
      $this->form->fill(['Name'   => $project->getName(),
                         'Hidden' => $projects->isHidden($project->getId())]);
    }
    
    return $this;
  }
  
  public function getForm(): FormComponent{
    return $this->form;
  }
  
  public function updateSettings(array $data): bool{
    if (!$this->form->accept($data)){
      return false;
    }
    
    $validator = new FormValidator($data);
    $name = ProjectFields::name($validator);
    $hidden = ProjectFields::hidden($validator);
    
    try{
      $validator->validate();
      $projects = new ProjectTable(DB::get());
      $projects->changeSettings($this->getProject()->getId(), $name, $hidden);
      $this->form->addMessage(FormComponent::MESSAGE_SUCCESS, Text::checkmark('Project settings were updated.'));
      return true;
    }catch(ValidationException $e){
      $this->form->invalidateFields($e->getFields());
    }catch(Exception $e){
      $this->form->onGeneralError($e);
    }
    
    return false;
  }
}

?>
