<?php
declare(strict_types = 1);

namespace Pages\Models\Tracker;

use Database\DB;
use Database\Objects\TrackerInfo;
use Database\Tables\TrackerTable;
use Database\Validation\TrackerFields;
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
  
  public function __construct(Request $req, TrackerInfo $tracker){
    parent::__construct($req, $tracker);
    
    $this->form = new FormComponent(self::ACTION_UPDATE);
    $this->form->startTitledSection('Tracker');
    $this->form->setMessagePlacementHere();
    
    $this->form->startSplitGroup(50);
    $this->form->addTextField('Name');
    $this->form->addTextField('Url')->value($tracker->getUrl())->disable();
    $this->form->endSplitGroup();
    
    $this->form->addCheckBox('Hidden')->label('Hidden From Non-Members');
    $this->form->addButton('submit', 'Update Settings')->icon('pencil');
    
    $this->form->endTitledSection();
  }
  
  public function load(): IModel{
    parent::load();
    
    if (!$this->form->isFilled()){
      $tracker = $this->getTracker();
      $trackers = new TrackerTable(DB::get());
      
      $this->form->fill(['Name'   => $tracker->getName(),
                         'Hidden' => $trackers->isHidden($tracker->getId())]);
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
    $name = TrackerFields::name($validator);
    $hidden = TrackerFields::hidden($validator);
    
    try{
      $validator->validate();
      $trackers = new TrackerTable(DB::get());
      $trackers->changeSettings($this->getTracker()->getId(), $name, $hidden);
      $this->form->addMessage(FormComponent::MESSAGE_SUCCESS, Text::checkmark('Tracker settings were updated.'));
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
