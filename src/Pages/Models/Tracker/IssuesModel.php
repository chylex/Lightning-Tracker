<?php
declare(strict_types = 1);

namespace Pages\Models\Tracker;

use Database\DB;
use Database\Filters\Pagination;
use Database\Filters\Types\IssueFilter;
use Database\Objects\TrackerInfo;
use Database\Tables\IssueTable;
use Pages\Components\ProgressBarComponent;
use Pages\Components\Sidemenu\SidemenuComponent;
use Pages\Components\Table\TableComponent;
use Pages\Components\Text;
use Pages\IModel;
use Pages\Models\BasicTrackerPageModel;
use Routing\Request;
use Session\Permissions;

class IssuesModel extends BasicTrackerPageModel{
  public const PERM_CREATE = 'issues.create';
  public const PERM_EDIT_ALL = 'issues.edit.all';
  public const PERM_DELETE_ALL = 'issues.delete.all';
  
  private Permissions $perms;
  private TableComponent $table;
  private SidemenuComponent $menu_actions;
  
  public function __construct(Request $req, TrackerInfo $tracker, Permissions $perms){
    parent::__construct($req, $tracker);
    
    $this->perms = $perms;
    
    $this->table = new TableComponent();
    $this->table->ifEmpty('No issues found.');
    
    $this->table->addColumn('')->tight()->collapsed();
    $this->table->addColumn('Title')->width(70)->bold();
    $this->table->addColumn('Priority')->tight();
    $this->table->addColumn('Scale')->tight();
    $this->table->addColumn('Status')->tight();
    $this->table->addColumn('Progress')->width(30);
    
    $this->menu_actions = new SidemenuComponent(BASE_URL_ENC, $req);
    $this->menu_actions->setTitle(Text::plain('Actions'));
  }
  
  public function load(): IModel{
    parent::load();
    
    $tracker = $this->getTracker();
    
    $filter = new IssueFilter();
    $issues = new IssueTable(DB::get(), $tracker);
    $total_count = $issues->countIssues($filter);
    
    $pagination = Pagination::fromGlobals($total_count);
    $filter = $filter->page($pagination);
    
    foreach($issues->listIssues($filter) as $issue){
      $row = $this->table->addRow([$issue->getType()->getViewable(true),
                                   $issue->getTitleSafe(),
                                   $issue->getPriority(),
                                   $issue->getScale(),
                                   $issue->getStatus(),
                                   new ProgressBarComponent($issue->getProgress())]);
      
      $row->link($this->getReq()->getBasePath()->encoded().'/issues/'.$issue->getId());
    }
    
    $this->table->setPaginationFooter($this->getReq(), $pagination)->elementName('issues');
    
    if ($this->perms->checkTracker($tracker, self::PERM_CREATE)){
      $this->menu_actions->addLink(Text::withIcon('New Issue', 'pencil'), '/issues/new');
    }
    
    return $this;
  }
  
  public function getIssueTable(): TableComponent{
    return $this->table;
  }
  
  public function getMenuActions(): ?SidemenuComponent{
    return $this->menu_actions->getIfNotEmpty();
  }
}

?>
