<?php
declare(strict_types = 1);

namespace Pages\Models\Tracker;

use Database\DB;
use Database\Filters\Types\MilestoneFilter;
use Database\Objects\TrackerInfo;
use Database\Tables\MilestoneTable;
use Database\Tables\TrackerUserSettingsTable;
use Exception;
use Pages\Components\CompositeComponent;
use Pages\Components\DateTimeComponent;
use Pages\Components\Forms\FormComponent;
use Pages\Components\ProgressBarComponent;
use Pages\Components\Table\TableComponent;
use Pages\Components\Text;
use Pages\IModel;
use Pages\Models\BasicTrackerPageModel;
use Routing\Request;
use Session\Permissions;
use Session\Session;
use Validation\ValidationException;
use Validation\Validator;

class MilestonesModel extends BasicTrackerPageModel{
  public const ACTION_CREATE = 'Create';
  public const ACTION_MOVE = 'Move';
  public const ACTION_TOGGLE_ACTIVE = 'ToggleActive';
  public const ACTION_DELETE = 'Delete';
  
  private const ACTION_MOVE_UP = 'Up';
  private const ACTION_MOVE_DOWN = 'Down';
  
  public const PERM_EDIT = 'milestones.edit';
  
  private Permissions $perms;
  private TableComponent $table;
  private ?FormComponent $form;
  
  public function __construct(Request $req, TrackerInfo $tracker, Permissions $perms){
    parent::__construct($req, $tracker);
    
    $this->perms = $perms;
    
    $this->table = new TableComponent();
    $this->table->ifEmpty('No milestones found.');
    
    $this->table->addColumn('Title')->width(65)->bold();
    $this->table->addColumn('Active')->tight()->center();
    $this->table->addColumn('Issues')->tight();
    $this->table->addColumn('Progress')->width(35);
    $this->table->addColumn('Last Updated')->tight()->right();
    
    if ($this->perms->checkTracker($tracker, self::PERM_EDIT)){
      $this->table->addColumn('Actions')->tight()->right();
      
      $this->form = new FormComponent(self::ACTION_CREATE);
      $this->form->startTitledSection('Create Milestone');
      $this->form->setMessagePlacementHere();
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
    
    $pagination = $filter->page($total_count);
    
    $active_milestone = $this->getActiveMilestone();
    $active_milestone_id = $active_milestone === null ? null : $active_milestone->getId();
    
    foreach($milestones->listMilestones($filter) as $milestone){
      $milestone_id = $milestone->getId();
      $milestone_id_str = strval($milestone_id);
      $update_date = $milestone->getLastUpdateDate();
      
      $form_toggle_active = new FormComponent(self::ACTION_TOGGLE_ACTIVE);
      $form_toggle_active->addHidden('Milestone', $milestone_id_str);
      $form_toggle_active->addIconButton('submit', $milestone_id === $active_milestone_id ? 'radio-checked' : 'radio-unchecked')->color('purple');
      
      $row = [$milestone->getTitleSafe(),
              $form_toggle_active,
              $milestone->getClosedIssues().' / '.$milestone->getTotalIssues(),
              new ProgressBarComponent($milestone->getPercentageDone()),
              $update_date === null ? Text::plain('<div class="center-text">-</div>') : new DateTimeComponent($update_date, true)];
      
      if ($this->perms->checkTracker($tracker, self::PERM_EDIT)){
        $form_move = new FormComponent(self::ACTION_MOVE);
        $form_move->addHidden('Milestone', $milestone_id_str);
        $form_move->addIconButton('submit', 'circle-up')->color('blue')->value(self::ACTION_MOVE_UP);
        $form_move->addIconButton('submit', 'circle-down')->color('blue')->value(self::ACTION_MOVE_DOWN);
        
        $form_delete = new FormComponent(self::ACTION_DELETE);
        $form_delete->requireConfirmation('This action cannot be reversed. Do you want to continue?');
        $form_delete->addHidden('Milestone', $milestone_id_str);
        $form_delete->addIconButton('submit', 'circle-cross')->color('red')->flushLeft();
        
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
    $validator->str('Title', $title)->notEmpty()->maxLength(64);
    
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
  
  public function toggleActiveMilestone(array $data): bool{
    if (!isset($data['Milestone']) || !is_numeric($data['Milestone'])){
      return false;
    }
    
    $logon_user = Session::get()->getLogonUser();
    
    if ($logon_user === null){
      return false;
    }
    
    $settings = new TrackerUserSettingsTable(DB::get(), $this->getTracker());
    $settings->toggleActiveMilestone($logon_user, (int)$data['Milestone']);
    return true;
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
