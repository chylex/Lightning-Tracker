<?php
declare(strict_types = 1);

namespace Pages\Views\Project;

use Pages\Components\CompositeComponent;
use Pages\Components\DateTimeComponent;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Forms\IconButtonFormComponent;
use Pages\Components\ProgressBarComponent;
use Pages\Components\SplitComponent;
use Pages\Components\Table\TableComponent;
use Pages\Components\TitledSectionComponent;
use Pages\Models\Project\MilestonesModel;
use Pages\Views\AbstractProjectPage;
use Routing\Link;

class MilestonesPage extends AbstractProjectPage{
  private MilestonesModel $model;
  
  public function __construct(MilestonesModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getHeading(): string{
    return 'Milestones';
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_FULL;
  }
  
  protected function echoPageHead(): void{
    SplitComponent::echoHead();
    TableComponent::echoHead();
    FormComponent::echoHead();
    ProgressBarComponent::echoHead();
    DateTimeComponent::echoHead();
  }
  
  protected function echoPageBody(): void{
    $split = new SplitComponent(75);
    $split->collapseAt(1024, true);
    $split->setRightWidthLimits(250, 500);
    
    $split->addLeft($this->createMilestoneTable());
    $split->addRightIfNotNull(TitledSectionComponent::wrap('Create Milestone', $this->model->getCreateForm()));
    
    $split->echoBody();
  }
  
  private function createMilestoneTable(): TableComponent{
    $req = $this->model->getReq();
    $can_manage_milestones = $this->model->canManageMilestones();
    
    $table = new TableComponent();
    $table->ifEmpty('No milestones found.');
    
    $table->addColumn('Title')->sort('title')->width(65)->wrap()->bold();
    $table->addColumn('Active')->tight()->center();
    $table->addColumn('Issues')->tight()->center();
    $table->addColumn('Progress')->sort('progress')->width(35);
    $table->addColumn('Last Updated')->sort('date_updated')->tight()->right();
    
    if ($can_manage_milestones){
      $table->addColumn('Actions')->tight()->right();
    }
    
    $filter = $this->model->prepareMilestoneTableFilter($table);
    
    foreach($this->model->getMilestones($filter) as $info){
      $milestone = $info->getMilestone();
      $milestone_id_str = (string)$milestone->getMilestoneId();
      $update_date = $milestone->getLastUpdateDate();
      
      $row = [$milestone->getTitleSafe(),
              $this->model->createToggleActiveForm($milestone),
              $milestone->getClosedIssues().' / '.$milestone->getTotalIssues(),
              new ProgressBarComponent($milestone->getPercentageDone()),
              $update_date === null ? '<div class="center-text">-</div>' : new DateTimeComponent($update_date, true)];
      
      if ($can_manage_milestones){
        $link_delete = Link::fromBase($req, 'milestones', $milestone_id_str, 'delete');
        $btn_delete = new IconButtonFormComponent($link_delete, 'circle-cross');
        $btn_delete->color('red');
        
        $row[] = CompositeComponent::nonNull($this->model->createMoveForm($info), $btn_delete);
      }
      else{
        $row[] = '';
      }
      
      $row = $table->addRow($row);
      
      if ($can_manage_milestones){
        $row->link(Link::fromBase($req, 'milestones', $milestone_id_str));
      }
    }
    
    return $table;
  }
}

?>
