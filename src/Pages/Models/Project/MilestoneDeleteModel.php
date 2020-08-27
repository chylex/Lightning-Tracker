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
  private bool $has_milestone = false;
  private string $milestone_title_safe;
  private int $milestone_issue_count;
  
  private FormComponent $form;
  
  public function __construct(Request $req, ProjectInfo $project, int $milestone_id){
    parent::__construct($req, $project);
    $this->milestone_id = $milestone_id;
    
    $this->form = new FormComponent(self::ACTION_CONFIRM);
    
    $select_milestone = $this->form->addSelect('Replacement')
                                   ->addOption('', '(No Milestone)')
                                   ->dropdown();
    
    foreach((new MilestoneTable(DB::get(), $project))->listMilestones() as $milestone){
      $id = $milestone->getMilestoneId();
      
      if ($id === $milestone_id){
        $this->has_milestone = true;
        $this->milestone_title_safe = $milestone->getTitleSafe();
        
        $issues = new IssueTable(DB::get(), $project);
        $filter = new IssueFilter();
        $filter->filterManual(['milestone' => [$id]]);
        $this->milestone_issue_count = $issues->countIssues($filter) ?? 0;
      }
      else{
        $select_milestone->addOption((string)$id, $milestone->getTitle());
      }
    }
    
    $this->form->addButton('submit', 'Delete Milestone')->icon('trash');
  }
  
  public function hasMilestone(): bool{
    return $this->has_milestone;
  }
  
  public function getMilestoneTitleSafe(): string{
    return $this->milestone_title_safe;
  }
  
  public function getMilestoneIssueCount(): int{
    return $this->milestone_issue_count;
  }
  
  public function getDeleteForm(): FormComponent{
    return $this->form;
  }
  
  public function deleteMilestoneSafely(): bool{
    if (!$this->has_milestone || $this->milestone_issue_count > 0){
      return false;
    }
    
    try{
      $milestones = new MilestoneTable(DB::get(), $this->getProject());
      $milestones->deleteById($this->milestone_id, null);
      return true;
    }catch(Exception $e){
      $this->form->onGeneralError($e);
    }
    
    return false;
  }
  
  public function deleteMilestone(array $data): bool{
    if (!$this->form->accept($data)){
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
      $this->form->invalidateField('Replacement', 'Invalid milestone.');
      return false;
    }
    
    try{
      $milestones = new MilestoneTable(DB::get(), $this->getProject());
      $milestones->deleteById($this->milestone_id, $replacement);
      return true;
    }catch(Exception $e){
      $this->form->onGeneralError($e);
    }
    
    return false;
  }
}

?>
