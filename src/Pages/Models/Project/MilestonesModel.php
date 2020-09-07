<?php
declare(strict_types = 1);

namespace Pages\Models\Project;

use Database\DB;
use Database\Filters\Types\MilestoneFilter;
use Database\Objects\ProjectInfo;
use Database\Tables\MilestoneTable;
use Database\Tables\ProjectUserSettingsTable;
use Database\Validation\MilestoneFields;
use Exception;
use Pages\Components\CompositeComponent;
use Pages\Components\DateTimeComponent;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Forms\IconButtonFormComponent;
use Pages\Components\ProgressBarComponent;
use Pages\Components\Table\TableComponent;
use Pages\Models\BasicProjectPageModel;
use Routing\Link;
use Routing\Request;
use Session\Permissions\ProjectPermissions;
use Session\Session;
use Validation\FormValidator;
use Validation\ValidationException;

class MilestonesModel extends BasicProjectPageModel{
  public const ACTION_CREATE = 'Create';
  public const ACTION_MOVE = 'Move';
  public const ACTION_TOGGLE_ACTIVE = 'ToggleActive';
  
  private const ACTION_MOVE_UP = 'Up';
  private const ACTION_MOVE_DOWN = 'Down';
  
  private ProjectPermissions $perms;
  
  private FormComponent $create_form;
  
  public function __construct(Request $req, ProjectInfo $project, ProjectPermissions $perms){
    parent::__construct($req, $project);
    $this->perms = $perms;
  }
  
  public function createMilestoneTable(): TableComponent{
    $req = $this->getReq();
    
    $table = new TableComponent();
    $table->ifEmpty('No milestones found.');
    
    $table->addColumn('Title')->sort('title')->width(65)->wrap()->bold();
    $table->addColumn('Active')->tight()->center();
    $table->addColumn('Issues')->tight()->center();
    $table->addColumn('Progress')->sort('progress')->width(35);
    $table->addColumn('Last Updated')->sort('date_updated')->tight()->right();
    
    if ($this->perms->check(ProjectPermissions::MANAGE_MILESTONES)){
      $table->addColumn('Actions')->tight()->right();
    }
    
    $filter = new MilestoneFilter();
    $milestones = new MilestoneTable(DB::get(), $this->getProject());
    $total_count = $milestones->countMilestones($filter);
    
    $pagination = $filter->page($total_count);
    $sorting = $filter->sort($req);
    
    $active_milestone = $this->getActiveMilestone();
    $active_milestone_id = $active_milestone === null ? null : $active_milestone->getId();
    
    $ordering_limit = $milestones->findMaxOrdering();
    
    foreach($milestones->listMilestones($filter) as $milestone){
      $milestone_id = $milestone->getMilestoneId();
      $milestone_id_str = (string)$milestone_id;
      $update_date = $milestone->getLastUpdateDate();
      
      $form_toggle_active = new FormComponent(self::ACTION_TOGGLE_ACTIVE);
      $form_toggle_active->addHidden('Milestone', $milestone_id_str);
      $form_toggle_active->addIconButton('submit', $milestone_id === $active_milestone_id ? 'radio-checked' : 'radio-unchecked')->color('purple');
      
      $row = [$milestone->getTitleSafe(),
              $form_toggle_active,
              $milestone->getClosedIssues().' / '.$milestone->getTotalIssues(),
              new ProgressBarComponent($milestone->getPercentageDone()),
              $update_date === null ? '<div class="center-text">-</div>' : new DateTimeComponent($update_date, true)];
      
      if ($this->perms->check(ProjectPermissions::MANAGE_MILESTONES)){
        $form_move = new FormComponent(self::ACTION_MOVE);
        $form_move->addHidden('Ordering', (string)$milestone->getOrdering());
        
        $btn_move_up = $form_move->addIconButton('submit', 'circle-up')->color('blue')->value(self::ACTION_MOVE_UP);
        $btn_move_down = $form_move->addIconButton('submit', 'circle-down')->color('blue')->value(self::ACTION_MOVE_DOWN);
        
        $ordering = $milestone->getOrdering();
        
        if ($ordering === 1){
          $btn_move_up->disabled();
        }
        
        if ($ordering === $ordering_limit){
          $btn_move_down->disabled();
        }
        
        $link_delete = Link::fromBase($req, 'milestones', $milestone_id_str, 'delete');
        $btn_delete = new IconButtonFormComponent($link_delete, 'circle-cross');
        $btn_delete->color('red');
        
        $row[] = new CompositeComponent($form_move, $btn_delete);
      }
      else{
        $row[] = '';
      }
      
      $row = $table->addRow($row);
      
      if ($this->perms->check(ProjectPermissions::MANAGE_MILESTONES)){
        $row->link(Link::fromBase($req, 'milestones', $milestone_id_str));
      }
    }
    
    $table->setupColumnSorting($sorting);
    $table->setPaginationFooter($req, $pagination)->elementName('milestones');
    
    return $table;
  }
  
  public function getCreateForm(): ?FormComponent{
    if (!$this->perms->check(ProjectPermissions::MANAGE_MILESTONES)){
      return null;
    }
    
    if (isset($this->create_form)){
      return $this->create_form;
    }
    
    $form = new FormComponent(self::ACTION_CREATE);
    $form->addTextField('Title')->type('text');
    $form->addButton('submit', 'Create Milestone')->icon('pencil');
    
    return $this->create_form = $form;
  }
  
  public function createMilestone(array $data): bool{
    $form = $this->getCreateForm();
    
    if ($form === null || !$form->accept($data)){
      return false;
    }
    
    $validator = new FormValidator($data);
    $title = MilestoneFields::title($validator);
    
    try{
      $validator->validate();
      $milestones = new MilestoneTable(DB::get(), $this->getProject());
      $milestones->addMilestone($title);
      return true;
    }catch(ValidationException $e){
      $form->invalidateFields($e->getFields());
    }catch(Exception $e){
      $form->onGeneralError($e);
    }
    
    return false;
  }
  
  public function moveMilestone(array $data): bool{
    $button = $data[FormComponent::BUTTON_KEY] ?? null;
    $ordering = get_int($data, 'Ordering');
    
    if (($button !== self::ACTION_MOVE_UP && $button !== self::ACTION_MOVE_DOWN) || $ordering === null){
      return false;
    }
    
    $milestones = new MilestoneTable(DB::get(), $this->getProject());
    
    if ($button === self::ACTION_MOVE_UP){
      $milestones->swapMilestones($ordering, $ordering - 1);
      return true;
    }
    elseif ($button === self::ACTION_MOVE_DOWN){
      $milestones->swapMilestones($ordering, $ordering + 1);
      return true;
    }
    
    return false;
  }
  
  public function toggleActiveMilestone(array $data): bool{
    $milestone = get_int($data, 'Milestone');
    
    if ($milestone === null){
      return false;
    }
    
    $logon_user = Session::get()->getLogonUser();
    
    if ($logon_user === null){
      return false;
    }
    
    $settings = new ProjectUserSettingsTable(DB::get(), $this->getProject());
    $settings->toggleActiveMilestone($logon_user, (int)$data['Milestone']);
    return true;
  }
}

?>
