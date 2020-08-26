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
use Pages\IModel;
use Pages\Models\BasicRootPageModel;
use Routing\Request;
use Routing\UrlString;

class ProjectDeleteModel extends BasicRootPageModel{
  public const ACTION_CONFIRM = 'Confirm';
  
  private ProjectInfo $project;
  private FormComponent $form;
  
  /**
   * @var array Statistics about what will be deleted. Each entry includes [amount, singular text, plural text].
   */
  private array $deletion_stats;
  
  public function __construct(Request $req, ProjectInfo $project){
    parent::__construct($req);
    $this->project = $project;
    
    $this->form = new FormComponent(self::ACTION_CONFIRM);
    $this->form->addTextField('Name')->label('Project Name');
    $this->form->addButton('submit', 'Delete Project')->icon('trash');
  }
  
  protected function createNavigation(): NavigationComponent{
    return new NavigationComponent('Lightning Tracker', BASE_URL_ENC, new UrlString(''), new UrlString(''));
  }
  
  public function load(): IModel{
    parent::load();
    
    $db = DB::get();
    
    $this->deletion_stats = [
        [(new IssueTable($db, $this->project))->countIssues(), 'issue', 'issues'],
        [(new MilestoneTable($db, $this->project))->countMilestones(), 'milestone', 'milestones'],
        [(new ProjectMemberTable($db, $this->project))->countMembers(), 'member', 'members']
    ];
    
    return $this;
  }
  
  public function getProject(): ProjectInfo{
    return $this->project;
  }
  
  public function getConfirmationForm(): FormComponent{
    return $this->form;
  }
  
  public function getDeletionStats(): array{
    return $this->deletion_stats;
  }
  
  public function deleteProject(array $data): bool{
    if (!$this->form->accept($data)){
      return false;
    }
    
    $confirmation = $data['Name'] ?? null;
    
    if ($confirmation !== $this->project->getName()){
      $this->form->invalidateField('Name', 'Incorrect project name.');
      return false;
    }
    
    $projects = new ProjectTable(DB::get());
    $projects->deleteById($this->getProject()->getId());
    return true;
  }
}

?>
