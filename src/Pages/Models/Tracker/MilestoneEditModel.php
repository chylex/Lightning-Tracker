<?php
declare(strict_types = 1);

namespace Pages\Models\Tracker;

use Database\DB;
use Database\Objects\TrackerInfo;
use Database\Tables\MilestoneTable;
use Exception;
use Pages\Components\Forms\FormComponent;
use Pages\IModel;
use Pages\Models\BasicTrackerPageModel;
use Routing\Request;
use Validation\ValidationException;
use Validation\Validator;

class MilestoneEditModel extends BasicTrackerPageModel{
  private int $milestone_id;
  private bool $has_milestone;
  
  private FormComponent $form;
  
  public function __construct(Request $req, TrackerInfo $tracker, int $milestone_id){
    parent::__construct($req, $tracker);
    $this->milestone_id = $milestone_id;
    
    $this->form = new FormComponent();
    $this->form->addTextField('Title')->type('text');
    $this->form->addButton('submit', 'Edit Milestone')->icon('pencil');
  }
  
  public function load(): IModel{
    parent::load();
    
    if (!$this->form->isFilled()){
      $tracker = $this->getTracker();
      $milestones = new MilestoneTable(DB::get(), $tracker);
      
      $title = $milestones->getMilestoneTitle($this->milestone_id);
      
      if ($title === null){
        $this->has_milestone = false;
      }
      else{
        $this->has_milestone = true;
        $this->form->fill(['Title' => $title]);
      }
    }
    
    return $this;
  }
  
  public function hasMilestone(): bool{
    return $this->has_milestone;
  }
  
  public function getEditForm(): FormComponent{
    return $this->form;
  }
  
  public function editMilestone(array $data): bool{
    if (!$this->form->accept($data)){
      return false;
    }
    
    $title = $data['Title'];
    
    $validator = new Validator();
    $validator->str('Title', $title)->notEmpty()->maxLength(64);
    
    try{
      $validator->validate();
      $milestones = new MilestoneTable(DB::get(), $this->getTracker());
      $milestones->setMilestoneTitle($this->milestone_id, $title);
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
