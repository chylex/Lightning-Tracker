<?php
declare(strict_types = 1);

namespace Pages\Models\Project;

use Database\DB;
use Database\Filters\Types\IssueFilter;
use Database\Objects\ProjectInfo;
use Database\Tables\IssueTable;
use Database\Tables\MilestoneTable;
use Exception;
use Pages\Components\Forms\FormComponent;
use Pages\Models\BasicProjectPageModel;
use Routing\Request;

class MilestoneDeleteModel extends BasicProjectPageModel{
  public const ACTION_CONFIRM = 'Confirm';
  
  private int $milestone_id;
  private ?string $milestone_title;
  private int $milestone_issue_count;
  
  private FormComponent $delete_form;
  
  public function __construct(Request $req, ProjectInfo $project, int $milestone_id){
    parent::__construct($req, $project);
    $this->milestone_id = $milestone_id;
    $this->milestone_title = (new MilestoneTable(DB::get(), $project))->getMilestoneTitle($milestone_id);
    
    $issues = new IssueTable(DB::get(), $project);
    $filter = new IssueFilter();
    $filter->filterManual(['milestone' => [$milestone_id]]);
    $this->milestone_issue_count = $issues->countIssues($filter) ?? 0;
  }
  
  public function hasMilestone(): bool{
    return $this->milestone_title !== null;
  }
  
  public function getMilestoneTitleSafe(): string{
    return protect($this->milestone_title);
  }
  
  public function getMilestoneIssueCount(): int{
    return $this->milestone_issue_count;
  }
  
  public function getDeleteForm(): FormComponent{
    if (isset($this->delete_form)){
      return $this->delete_form;
    }
    
    $form = new FormComponent(self::ACTION_CONFIRM);
    
    $select_milestone = $form->addSelect('Replacement')
                             ->addOption('', '(No Milestone)')
                             ->dropdown();
    
    foreach((new MilestoneTable(DB::get(), $this->getProject()))->listMilestones() as $milestone){
      $id = $milestone->getMilestoneId();
      
      if ($id !== $this->milestone_id){
        $select_milestone->addOption((string)$id, $milestone->getTitle());
      }
    }
    
    $form->addButton('submit', 'Delete Milestone')->icon('trash');
    
    return $this->delete_form = $form;
  }
  
  public function deleteMilestoneSafely(): bool{
    if (!$this->hasMilestone() || $this->milestone_issue_count > 0){
      return false;
    }
    
    try{
      $milestones = new MilestoneTable(DB::get(), $this->getProject());
      $milestones->deleteById($this->milestone_id, null);
      return true;
    }catch(Exception $e){
      $this->getDeleteForm()->onGeneralError($e);
    }
    
    return false;
  }
  
  public function deleteMilestone(array $data): bool{
    $form = $this->getDeleteForm();
    
    if (!$form->accept($data)){
      return false;
    }
    
    $replacement = $data['Replacement'];
    
    if (empty($replacement)){
      $replacement = null;
    }
    elseif (is_numeric($replacement)){
      $replacement = (int)$replacement;
    }
    else{
      $form->invalidateField('Replacement', 'Invalid milestone.');
      return false;
    }
    
    try{
      $milestones = new MilestoneTable(DB::get(), $this->getProject());
      $milestones->deleteById($this->milestone_id, $replacement);
      return true;
    }catch(Exception $e){
      $form->onGeneralError($e);
    }
    
    return false;
  }
}

?>
