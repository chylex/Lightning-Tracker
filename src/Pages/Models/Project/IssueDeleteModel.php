<?php
declare(strict_types = 1);

namespace Pages\Models\Project;

use Database\DB;
use Database\Objects\IssueDetail;
use Database\Objects\ProjectInfo;
use Database\Tables\IssueTable;
use Pages\Components\Forms\FormComponent;
use Pages\Models\BasicProjectPageModel;
use Routing\Request;

class IssueDeleteModel extends BasicProjectPageModel{
  public const ACTION_CONFIRM = 'Confirm';
  
  private int $issue_id;
  private ?IssueDetail $issue;
  
  private FormComponent $delete_form;
  
  public function __construct(Request $req, ProjectInfo $project, int $issue_id){
    parent::__construct($req, $project);
    $this->issue_id = $issue_id;
    $this->issue = (new IssueTable(DB::get(), $project))->getIssueDetail($issue_id);
  }
  
  public function getIssueId(): int{
    return $this->issue_id;
  }
  
  public function getIssue(): ?IssueDetail{
    return $this->issue;
  }
  
  public function getDeleteForm(): FormComponent{
    if (isset($this->delete_form)){
      return $this->delete_form;
    }
    
    $form = new FormComponent(self::ACTION_CONFIRM);
    $form->addTextField('Id')->label('Issue ID');
    $form->addButton('submit', 'Delete Issue')->icon('trash');
    
    return $this->delete_form = $form;
  }
  
  public function deleteIssue(array $data): bool{
    $form = $this->getDeleteForm();
    
    if (!$form->accept($data)){
      return false;
    }
    
    $confirmation = $data['Id'] ?? null;
    
    if ($confirmation !== (string)$this->issue_id){
      $form->invalidateField('Id', 'Incorrect issue ID.');
      return false;
    }
    
    $issues = new IssueTable(DB::get(), $this->getProject());
    $issues->deleteById($this->issue_id);
    return true;
  }
}

?>
