<?php
declare(strict_types = 1);

namespace Pages\Models\Root;

use Database\DB;
use Database\Objects\ProjectInfo;
use Database\Tables\IssueTable;
use Database\Tables\MilestoneTable;
use Database\Tables\ProjectMemberTable;
use Database\Tables\ProjectTable;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Navigation\NavigationComponent;
use Pages\Models\BasicRootPageModel;
use Routing\Request;
use Routing\UrlString;

class ProjectDeleteModel extends BasicRootPageModel{
  public const ACTION_CONFIRM = 'Confirm';
  
  private ProjectInfo $project;
  
  private FormComponent $delete_form;
  
  public function __construct(Request $req, ProjectInfo $project){
    parent::__construct($req);
    $this->project = $project;
  }
  
  protected function createNavigation(): NavigationComponent{
    return new NavigationComponent('Lightning Tracker', BASE_URL_ENC, new UrlString(''), new UrlString(''));
  }
  
  public function getProject(): ProjectInfo{
    return $this->project;
  }
  
  /**
   * @return array Statistics about what will be deleted. Each entry includes [amount, singular text, plural text].
   */
  public function calculateDeletionStats(): array{
    $db = DB::get();
    
    return [
        [(new IssueTable($db, $this->project))->countIssues(), 'issue', 'issues'],
        [(new MilestoneTable($db, $this->project))->countMilestones(), 'milestone', 'milestones'],
        [(new ProjectMemberTable($db, $this->project))->countMembers(), 'member', 'members'],
    ];
  }
  
  public function getConfirmationForm(): FormComponent{
    if (isset($this->delete_form)){
      return $this->delete_form;
    }
    
    $form = new FormComponent(self::ACTION_CONFIRM);
    $form->addTextField('Name')->label('Project Name');
    $form->addButton('submit', 'Delete Project')->icon('trash');
    
    return $this->delete_form = $form;
  }
  
  public function deleteProject(array $data): bool{
    $form = $this->getConfirmationForm();
    
    if (!$form->accept($data)){
      return false;
    }
    
    $confirmation = $data['Name'] ?? null;
    
    if ($confirmation !== $this->project->getName()){
      $form->invalidateField('Name', 'Incorrect project name.');
      return false;
    }
    
    $projects = new ProjectTable(DB::get());
    $projects->deleteById($this->getProject()->getId());
    return true;
  }
}

?>
