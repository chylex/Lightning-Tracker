<?php
declare(strict_types = 1);

namespace Pages\Views\Project;

use Pages\Components\DateTimeComponent;
use Pages\Components\Forms\FormComponent;
use Pages\Components\ProgressBarComponent;
use Pages\Components\Sidemenu\SidemenuComponent;
use Pages\Components\SplitComponent;
use Pages\Components\Table\TableComponent;
use Pages\Models\Project\IssuesModel;
use Pages\Views\AbstractProjectPage;
use Routing\Link;

class IssuesPage extends AbstractProjectPage{
  private IssuesModel $model;
  
  public function __construct(IssuesModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getHeading(): string{
    return 'Issues';
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_FULL;
  }
  
  protected function echoPageHead(): void{
    SplitComponent::echoHead();
    TableComponent::echoHead();
    FormComponent::echoHead();
    SidemenuComponent::echoHead();
    ProgressBarComponent::echoHead();
    DateTimeComponent::echoHead();
    
    if (DEBUG){
      echo '<link rel="stylesheet" type="text/css" href="~resources/css/issues.css?v='.TRACKER_RESOURCE_VERSION.'">';
    }
    
    echo '<script type="text/javascript" src="~resources/js/issues.js?v='.TRACKER_RESOURCE_VERSION.'"></script>';
  }
  
  protected function echoPageBody(): void{
    $split = new SplitComponent(80);
    $split->collapseAt(1024, true);
    $split->setRightWidthLimits(250, 400);
    
    $split->addLeft($this->createIssueTable());
    $split->addRightIfNotNull($this->model->createMenuAction());
    $split->addRightIfNotNull($this->model->getActiveMilestoneComponent());
    
    $split->echoBody();
  }
  
  public function createIssueTable(): TableComponent{
    $req = $this->model->getReq();
    
    $table = new TableComponent();
    $table->ifEmpty('No issues found.');
    
    $table->addColumn('')->tight()->collapsed();
    $table->addColumn('ID')->sort('id')->tight()->collapsed()->right()->bold();
    $table->addColumn('Title')->sort('title')->width(70)->collapsed()->wrap()->bold();
    $table->addColumn('Priority')->sort('priority')->tight();
    $table->addColumn('Scale')->sort('scale')->tight();
    $table->addColumn('Status')->tight();
    $table->addColumn('Progress')->sort('progress')->width(30);
    $table->addColumn('Last Update')->sort('date_updated')->tight()->right();
    
    $filter = $this->model->setupIssueTableFilter($table);
    
    foreach($this->model->getIssues($filter) as $issue){
      $issue_id = $issue->getId();
      
      $row = $table->addRow([$issue->getType()->getViewable(true),
                             '<span class="issue-id">#'.$issue_id.'</span>',
                             $issue->getTitleSafe(),
                             $issue->getPriority(),
                             $issue->getScale(),
                             $issue->getStatus(),
                             new ProgressBarComponent($issue->getProgress()),
                             new DateTimeComponent($issue->getLastUpdateDate())]);
      
      $row->link(Link::fromBase($req, 'issues', $issue_id));
    }
    
    return $table;
  }
}

?>
