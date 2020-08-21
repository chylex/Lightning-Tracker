<?php
declare(strict_types = 1);

namespace Pages\Models\Tracker;

use Database\DB;
use Database\Objects\TrackerInfo;
use Database\Tables\MilestoneTable;
use Database\Validation\MilestoneFields;
use Exception;
use Pages\Components\Forms\FormComponent;
use Pages\IModel;
use Pages\Models\BasicTrackerPageModel;
use Routing\Request;
use Validation\FormValidator;
use Validation\ValidationException;

class MilestoneEditModel extends BasicTrackerPageModel{
  public const ACTION_EDIT = 'Edit';
  
  private int $milestone_id;
  private ?string $milestone_title;
  
  private FormComponent $form;
  
  public function __construct(Request $req, TrackerInfo $tracker, int $milestone_id){
    parent::__construct($req, $tracker);
    $this->milestone_id = $milestone_id;
    
    $this->form = new FormComponent(self::ACTION_EDIT);
    $this->form->addTextField('Title')->type('text');
    $this->form->addButton('submit', 'Edit Milestone')->icon('pencil');
  }
  
  public function load(): IModel{
    parent::load();
    
    $this->milestone_title = (new MilestoneTable(DB::get(), $this->getTracker()))->getMilestoneTitle($this->milestone_id);
    
    if ($this->milestone_title !== null && !$this->form->isFilled()){
      $this->form->fill(['Title' => $this->milestone_title]);
    }
    
    return $this;
  }
  
  public function hasMilestone(): bool{
    return $this->milestone_title !== null;
  }
  
  public function getMilestoneTitleSafe(): string{
    return protect($this->milestone_title);
  }
  
  public function getEditForm(): FormComponent{
    return $this->form;
  }
  
  public function editMilestone(array $data): bool{
    if (!$this->form->accept($data)){
      return false;
    }
    
    $validator = new FormValidator($data);
    $title = MilestoneFields::title($validator);
    
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
