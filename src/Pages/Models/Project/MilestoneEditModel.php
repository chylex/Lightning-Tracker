<?php
declare(strict_types = 1);

namespace Pages\Models\Project;

use Database\DB;
use Database\Objects\ProjectInfo;
use Database\Tables\MilestoneTable;
use Database\Validation\MilestoneFields;
use Exception;
use Pages\Components\Forms\FormComponent;
use Pages\IModel;
use Pages\Models\BasicProjectPageModel;
use Routing\Request;
use Validation\FormValidator;
use Validation\ValidationException;

class MilestoneEditModel extends BasicProjectPageModel{
  public const ACTION_CONFIRM = 'Confirm';
  
  private int $milestone_id;
  private ?string $milestone_title;
  
  private FormComponent $edit_form;
  
  public function __construct(Request $req, ProjectInfo $project, int $milestone_id){
    parent::__construct($req, $project);
    $this->milestone_id = $milestone_id;
    $this->milestone_title = (new MilestoneTable(DB::get(), $project))->getMilestoneTitle($milestone_id);
  }
  
  public function load(): IModel{
    parent::load();
    
    if ($this->milestone_title !== null){
      $form = $this->getEditForm();
      
      if (!$form->isFilled()){
        $form->fill(['Title' => $this->milestone_title]);
      }
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
    if (isset($this->edit_form)){
      return $this->edit_form;
    }
    
    $form = new FormComponent(self::ACTION_CONFIRM);
    $form->addTextField('Title')->type('text');
    $form->addButton('submit', 'Edit Milestone')->icon('pencil');
    
    return $this->edit_form = $form;
  }
  
  public function editMilestone(array $data): bool{
    $form = $this->getEditForm();
    
    if (!$form->accept($data)){
      return false;
    }
    
    $validator = new FormValidator($data);
    $title = MilestoneFields::title($validator);
    
    try{
      $validator->validate();
      $milestones = new MilestoneTable(DB::get(), $this->getProject());
      $milestones->setMilestoneTitle($this->milestone_id, $title);
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
