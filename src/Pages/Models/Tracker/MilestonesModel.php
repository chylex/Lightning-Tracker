<?php
declare(strict_types = 1);

namespace Pages\Models\Tracker;

use Database\DB;
use Database\Filters\AbstractFilter;
use Database\Filters\Pagination;
use Database\Filters\Types\MilestoneFilter;
use Database\Objects\TrackerInfo;
use Database\Tables\MilestoneTable;
use Exception;
use Pages\Components\CompositeComponent;
use Pages\Components\DateTimeComponent;
use Pages\Components\Forms\FormComponent;
use Pages\Components\ProgressBarComponent;
use Pages\Components\Table\TableComponent;
use Pages\IModel;
use Pages\Models\BasicTrackerPageModel;
use Routing\Request;
use Session\Permissions;
use Validation\ValidationException;
use Validation\Validator;

class MilestonesModel extends BasicTrackerPageModel{
  public const ACTION_CREATE = 'Create';
  public const ACTION_DELETE = 'Delete';
  public const ACTION_MOVE = 'Move';
  
  private const ACTION_MOVE_UP = 'Up';
  private const ACTION_MOVE_DOWN = 'Down';
  
  public const PERM_EDIT = 'milestones.edit';
  
  private const MILESTONES_PER_PAGE = 15;
  
  private Permissions $perms;
  private TableComponent $table;
  private ?FormComponent $form;
  
  public function __construct(Request $req, TrackerInfo $tracker, Permissions $perms){
    parent::__construct($req, $tracker);
    
    $this->perms = $perms;
    
    $this->table = new TableComponent();
    $this->table->ifEmpty('No milestones found.');
    
    $this->table->addColumn('Title')->width(65)->bold();
    $this->table->addColumn('Issues')->tight();
    $this->table->addColumn('Progress')->width(35);
    $this->table->addColumn('Last Updated')->tight()->right();
    
    if ($this->perms->checkTracker($tracker, self::PERM_EDIT)){
      $this->table->addColumn('Actions')->tight()->right();
      
      $this->form = new FormComponent(self::ACTION_CREATE);
      $this->form->startTitledSection('Create Milestone');
      $this->form->addTextField('Title')->type('text');
      $this->form->addButton('submit', 'Create Milestone')->icon('pencil');
      $this->form->endTitledSection();
    }
    else{
      $this->form = null;
    }
  }
  
  public function load(): IModel{
    parent::load();
    
    $tracker = $this->getTracker();
    
    $filter = new MilestoneFilter();
    $milestones = new MilestoneTable(DB::get(), $this->getTracker());
    $total_count = $milestones->countMilestones($filter);
    
    $pagination = Pagination::fromGet(AbstractFilter::GET_PAGE, $total_count, self::MILESTONES_PER_PAGE);
    $filter = $filter->page($pagination);
    
    foreach($milestones->listMilestones($filter) as $milestone){
      $row = [$milestone->getTitleSafe(),
              $milestone->getClosedIssues().' / '.$milestone->getTotalIssues(),
              new ProgressBarComponent($milestone->getPercentageDone()),
              new DateTimeComponent($milestone->getLastUpdateDate(), true)];
      
      if ($this->perms->checkTracker($tracker, self::PERM_EDIT)){
        $milestone_id = strval($milestone->getId());
        
        $form_move = new FormComponent(self::ACTION_MOVE);
        $form_move->addHidden('Milestone', $milestone_id);
        $form_move->addIconButton('submit', 'circle-up')->value(self::ACTION_MOVE_UP);
        $form_move->addIconButton('submit', 'circle-down')->value(self::ACTION_MOVE_DOWN);
        
        $form_delete = new FormComponent(self::ACTION_DELETE);
        $form_delete->requireConfirmation('This action cannot be reversed. Do you want to continue?');
        $form_delete->addHidden('Milestone', $milestone_id);
        $form_delete->addIconButton('submit', 'trash');
        
        $row[] = new CompositeComponent($form_move, $form_delete);
      }
      else{
        $row[] = '';
      }
      
      $this->table->addRow($row);
    }
    
    $this->table->setPaginationFooter($this->getReq(), $pagination)->elementName('milestones');
    
    return $this;
  }
  
  public function getMilestoneTable(): TableComponent{
    return $this->table;
  }
  
  public function getCreateForm(): ?FormComponent{
    return $this->form;
  }
  
  public function createMilestone(array $data): bool{
    $tracker = $this->getTracker();
    $this->perms->requireTracker($tracker, self::PERM_EDIT);
    
    if (!$this->form->accept($data)){
      return false;
    }
    
    $title = $data['Title'];
    
    $validator = new Validator();
    $validator->str('Title', $title)->notEmpty();
    
    try{
      $validator->validate();
      $milestones = new MilestoneTable(DB::get(), $tracker);
      $milestones->addMilestone($title);
      return true;
    }catch(ValidationException $e){
      $this->form->invalidateFields($e->getFields());
    }catch(Exception $e){
      $this->form->onGeneralError($e);
    }
    
    return false;
  }
  
  public function moveMilestone(array $data): bool{
    $tracker = $this->getTracker();
    $this->perms->requireTracker($tracker, self::PERM_EDIT);
    
    $type = $data[FormComponent::SUB_ACTION_KEY] ?? null;
    
    if (($type !== self::ACTION_MOVE_UP && $type !== self::ACTION_MOVE_DOWN) || !isset($data['Milestone']) || !is_numeric($data['Milestone'])){
      return false;
    }
    
    $milestones = new MilestoneTable(DB::get(), $this->getTracker());
    
    if ($type === self::ACTION_MOVE_UP){
      $milestones->moveMilestoneUp((int)$data['Milestone']);
      return true;
    }
    elseif ($type === self::ACTION_MOVE_DOWN){
      $milestones->moveMilestoneDown((int)$data['Milestone']);
      return true;
    }
    
    return false;
  }
  
  public function deleteMilestone(array $data): bool{ // TODO make it a dedicated page with additional checks
    $tracker = $this->getTracker();
    $this->perms->requireTracker($tracker, self::PERM_EDIT);
    
    if (!isset($data['Milestone']) || !is_numeric($data['Milestone'])){
      return false;
    }
    
    $milestones = new MilestoneTable(DB::get(), $tracker);
    $milestones->deleteById((int)$data['Milestone']);
    return true;
  }
}

?>
