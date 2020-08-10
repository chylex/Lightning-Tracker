<?php
declare(strict_types = 1);

namespace Pages\Models\Tracker;

use Database\DB;
use Database\Objects\TrackerInfo;
use Database\Tables\TrackerTable;
use Exception;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Text;
use Pages\IModel;
use Pages\Models\BasicTrackerPageModel;
use Routing\Request;
use Validation\ValidationException;
use Validation\Validator;

class SettingsModel extends BasicTrackerPageModel{
  public const PERM = 'settings';
  
  private FormComponent $form;
  
  public function __construct(Request $req, TrackerInfo $tracker){
    parent::__construct($req, $tracker);
    
    $this->form = new FormComponent();
    $this->form->startTitledSection('Tracker');
    $this->form->setMessagePlacementHere();
    $this->form->addTextField('Name');
    $this->form->addTextField('Url')->value($tracker->getUrl())->disable();
    $this->form->addCheckBox('Hidden')->label('Hidden From Non-Members');
    $this->form->addButton('submit', 'Update Settings')->icon('pencil');
    $this->form->endTitledSection();
  }
  
  public function load(): IModel{
    parent::load();
    
    if (!$this->form->isFilled()){
      $tracker = $this->getTracker();
      $trackers = new TrackerTable(DB::get());
      
      $this->form->fill(['Name'   => $tracker->getNameSafe(),
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
    
    $name = $data['Name'];
    $hidden = (bool)($data['Hidden'] ?? false);
    
    $validator = new Validator();
    $validator->str('Name', $name)->notEmpty()->maxLength(32);
    
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
