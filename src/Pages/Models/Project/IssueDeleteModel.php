<?php
declare(strict_types = 1);

namespace Pages\Models\Project;

use Database\DB;
use Database\Objects\IssueDetail;
use Database\Objects\ProjectInfo;
use Database\Tables\IssueTable;
use Pages\Components\Forms\FormComponent;
use Pages\IModel;
use Pages\Models\BasicProjectPageModel;
use Routing\Request;

class IssueDeleteModel extends BasicProjectPageModel{
  public const ACTION_CONFIRM = 'Confirm';
  
  private ?IssueDetail $issue = null;
  private int $issue_id;
  
  private FormComponent $form;
  
  public function __construct(Request $req, ProjectInfo $project, int $issue_id){
    parent::__construct($req, $project);
    $this->issue_id = $issue_id;
    
    $this->form = new FormComponent(self::ACTION_CONFIRM);
    $this->form->addTextField('Id')->label('Issue ID');
    $this->form->addButton('submit', 'Delete Issue')->icon('trash');
  }
  
  public function load(): IModel{
    parent::load();
    
    $issues = new IssueTable(DB::get(), $this->getProject());
    $this->issue = $issues->getIssueDetail($this->issue_id);
    
    return $this;
  }
  
  public function getIssue(): ?IssueDetail{
    return $this->issue;
  }
  
  public function getIssueId(): int{
    return $this->issue_id;
  }
  
  public function getConfirmationForm(): FormComponent{
    return $this->form;
  }
  
  public function deleteIssue(array $data): bool{
    if (!$this->form->accept($data)){
      return false;
    }
    
    $confirmation = $data['Id'] ?? null;
    
    if ($confirmation !== (string)$this->issue_id){
      $this->form->invalidateField('Id', 'Incorrect issue ID.');
      return false;
    }
    
    $issues = new IssueTable(DB::get(), $this->getProject());
    $issues->deleteById($this->issue_id);
    return true;
  }
}

?>
