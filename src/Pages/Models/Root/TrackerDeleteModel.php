<?php
declare(strict_types = 1);

namespace Pages\Models\Root;

use Database\DB;
use Database\Objects\TrackerInfo;
use Database\Tables\IssueTable;
use Database\Tables\MilestoneTable;
use Database\Tables\TrackerMemberTable;
use Database\Tables\TrackerTable;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Navigation\NavigationComponent;
use Pages\IModel;
use Pages\Models\BasicRootPageModel;
use Routing\Request;
use Routing\UrlString;

class TrackerDeleteModel extends BasicRootPageModel{
  public const ACTION_CONFIRM = 'Confirm';
  
  private TrackerInfo $tracker;
  private FormComponent $form;
  
  /**
   * @var array Statistics about what will be deleted. Each entry includes [amount, singular text, plural text].
   */
  private array $deletion_stats;
  
  public function __construct(Request $req, TrackerInfo $tracker){
    parent::__construct($req);
    $this->tracker = $tracker;
    
    $this->form = new FormComponent(self::ACTION_CONFIRM);
    $this->form->addTextField('Name')->label('Tracker Name');
    $this->form->addButton('submit', 'Delete')->icon('trash');
  }
  
  protected function createNavigation(): NavigationComponent{
    return new NavigationComponent('Lightning Tracker', BASE_URL_ENC, new UrlString(''), new UrlString(''));
  }
  
  public function load(): IModel{
    parent::load();
    
    $db = DB::get();
    
    $this->deletion_stats = [
        [(new IssueTable($db, $this->tracker))->countIssues(), 'issue', 'issues'],
        [(new MilestoneTable($db, $this->tracker))->countMilestones(), 'milestone', 'milestones'],
        [(new TrackerMemberTable($db, $this->tracker))->countMembers(), 'member', 'members']
    ];
    
    return $this;
  }
  
  public function getTracker(): TrackerInfo{
    return $this->tracker;
  }
  
  public function getConfirmationForm(): FormComponent{
    return $this->form;
  }
  
  public function getDeletionStats(): array{
    return $this->deletion_stats;
  }
  
  public function deleteTracker(array $data): bool{
    if (!$this->form->accept($data)){
      return false;
    }
    
    $confirmation = $data['Name'] ?? null;
    
    if ($confirmation !== $this->tracker->getName()){
      $this->form->invalidateField('Name', 'Incorrect tracker name.');
      return false;
    }
    
    $trackers = new TrackerTable(DB::get());
    $trackers->deleteById($this->getTracker()->getId());
    return true;
  }
}

?>
