<?php
declare(strict_types = 1);

namespace Pages\Models\Project;

use Database\DB;
use Database\Filters\Types\MilestoneFilter;
use Database\Objects\MilestoneInfo;
use Database\Objects\MilestoneManagementInfo;
use Database\Objects\ProjectInfo;
use Database\Tables\MilestoneTable;
use Database\Tables\ProjectUserSettingsTable;
use Database\Validation\MilestoneFields;
use Exception;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Table\TableComponent;
use Pages\Models\BasicProjectPageModel;
use Routing\Request;
use Session\Permissions\ProjectPermissions;
use Session\Session;
use Validation\FormValidator;
use Validation\ValidationException;

class MilestonesModel extends BasicProjectPageModel{
  public const ACTION_CREATE = 'Create';
  public const ACTION_MOVE = 'Move';
  public const ACTION_TOGGLE_ACTIVE = 'ToggleActive';
  
  private const BUTTON_MOVE_UP = 'Up';
  private const BUTTON_MOVE_DOWN = 'Down';
  
  private ProjectPermissions $perms;
  
  private FormComponent $create_form;
  
  public function __construct(Request $req, ProjectInfo $project, ProjectPermissions $perms){
    parent::__construct($req, $project);
    $this->perms = $perms;
  }
  
  public function canManageMilestones(): bool{
    return $this->perms->check(ProjectPermissions::MANAGE_MILESTONES);
  }
  
  public function prepareMilestoneTableFilter(TableComponent $table): MilestoneFilter{
    $req = $this->getReq();
    
    $filter = new MilestoneFilter();
    $milestones = new MilestoneTable(DB::get(), $this->getProject());
    $total_count = $milestones->countMilestones($filter);
    
    $pagination = $filter->page($total_count);
    $sorting = $filter->sort($req);
    
    $table->setupColumnSorting($sorting);
    $table->setPaginationFooter($req, $pagination)->elementName('milestones');
    
    return $filter;
  }
  
  /**
   * @param MilestoneFilter $filter
   * @return MilestoneManagementInfo[]
   */
  public function getMilestones(MilestoneFilter $filter): array{
    $milestones = new MilestoneTable(DB::get(), $this->getProject());
    $ordering_limit = $milestones->findMaxOrdering();
    return array_map(fn(MilestoneInfo $v): MilestoneManagementInfo => new MilestoneManagementInfo($v, $ordering_limit), $milestones->listMilestones($filter));
  }
  
  public function getCreateForm(): ?FormComponent{
    if (!$this->canManageMilestones()){
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
  
  public function createToggleActiveForm(MilestoneInfo $milestone): FormComponent{
    $milestone_id = $milestone->getMilestoneId();
    $active_milestone = $this->getActiveMilestone();
    $active_milestone_id = $active_milestone === null ? null : $active_milestone->getId();
    
    $form = new FormComponent(MilestonesModel::ACTION_TOGGLE_ACTIVE);
    $form->addHidden('Milestone', (string)$milestone_id);
    $form->addIconButton('submit', $milestone_id === $active_milestone_id ? 'radio-checked' : 'radio-unchecked')->color('purple');
    
    return $form;
  }
  
  public function createMoveForm(MilestoneManagementInfo $info): ?FormComponent{
    if (!$this->canManageMilestones()){
      return null;
    }
    
    $form = new FormComponent(self::ACTION_MOVE);
    $form->addHidden('Ordering', (string)$info->getMilestone()->getOrdering());
    
    $btn_move_up = $form->addIconButton('submit', 'circle-up')->color('blue')->value(self::BUTTON_MOVE_UP);
    $btn_move_down = $form->addIconButton('submit', 'circle-down')->color('blue')->value(self::BUTTON_MOVE_DOWN);
    
    if (!$info->canMoveUp()){
      $btn_move_up->disabled();
    }
    
    if (!$info->canMoveDown()){
      $btn_move_down->disabled();
    }
    
    return $form;
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
    
    if (($button !== self::BUTTON_MOVE_UP && $button !== self::BUTTON_MOVE_DOWN) || $ordering === null){
      return false;
    }
    
    $milestones = new MilestoneTable(DB::get(), $this->getProject());
    
    if ($button === self::BUTTON_MOVE_UP){
      $milestones->swapMilestones($ordering, $ordering - 1);
      return true;
    }
    elseif ($button === self::BUTTON_MOVE_DOWN){
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
