<?php
declare(strict_types = 1);

namespace Pages\Models\Project;

use Database\DB;
use Database\Filters\Types\IssueFilter;
use Database\Objects\ProjectInfo;
use Database\Tables\IssueTable;
use Database\Tables\MilestoneTable;
use Database\Tables\ProjectMemberTable;
use Pages\Components\DateTimeComponent;
use Pages\Components\Forms\Elements\FormSelectMultiple;
use Pages\Components\Html;
use Pages\Components\Issues\IIssueTag;
use Pages\Components\Issues\IssuePriority;
use Pages\Components\Issues\IssueScale;
use Pages\Components\Issues\IssueStatus;
use Pages\Components\Issues\IssueType;
use Pages\Components\ProgressBarComponent;
use Pages\Components\Sidemenu\SidemenuComponent;
use Pages\Components\Table\TableComponent;
use Pages\Components\Text;
use Pages\IModel;
use Pages\Models\BasicProjectPageModel;
use Routing\Link;
use Routing\Request;
use Session\Permissions\ProjectPermissions;
use Session\Session;

class IssuesModel extends BasicProjectPageModel{
  /**
   * @param FormSelectMultiple $select
   * @param IIssueTag[] $items
   */
  private static function setupIssueTagOptions(FormSelectMultiple $select, array $items): void{
    foreach($items as $item){
      $select->addOption($item->getId(), new Html('<span class="'.$item->getTagClass().'"></span> '.$item->getTitle()));
    }
  }
  
  private ProjectPermissions $perms;
  private TableComponent $table;
  private SidemenuComponent $menu_actions;
  
  public function __construct(Request $req, ProjectInfo $project, ProjectPermissions $perms){
    parent::__construct($req, $project);
    
    $this->perms = $perms;
    
    $this->table = new TableComponent();
    $this->table->ifEmpty('No issues found.');
    
    $this->table->addColumn('')->tight()->collapsed();
    $this->table->addColumn('ID')->sort('id')->tight()->collapsed()->right()->bold();
    $this->table->addColumn('Title')->sort('title')->width(70)->collapsed()->wrap()->bold();
    $this->table->addColumn('Priority')->sort('priority')->tight();
    $this->table->addColumn('Scale')->sort('scale')->tight();
    $this->table->addColumn('Status')->tight();
    $this->table->addColumn('Progress')->sort('progress')->width(30);
    $this->table->addColumn('Last Update')->sort('date_updated')->tight()->right();
    
    $this->menu_actions = new SidemenuComponent($req);
    $this->menu_actions->setTitle(Text::plain('Actions'));
  }
  
  public function load(): IModel{
    parent::load();
    
    $project = $this->getProject();
    $logon_user_id = Session::get()->getLogonUserId();
    
    $filter = new IssueFilter();
    $issues = new IssueTable(DB::get(), $project);
    
    $filtering = $filter->filter();
    $total_count = $issues->countIssues($filter);
    $pagination = $filter->page($total_count);
    $sorting = $filter->sort($this->getReq());
    
    foreach($issues->listIssues($filter) as $issue){
      $issue_id = $issue->getId();
      
      $row = $this->table->addRow([$issue->getType()->getViewable(true),
                                   '<span class="issue-id">#'.$issue_id.'</span>',
                                   $issue->getTitleSafe(),
                                   $issue->getPriority(),
                                   $issue->getScale(),
                                   $issue->getStatus(),
                                   new ProgressBarComponent($issue->getProgress()),
                                   new DateTimeComponent($issue->getLastUpdateDate())]);
      
      $row->link(Link::fromBase($this->getReq(), 'issues', $issue_id));
    }
    
    $this->table->setupColumnSorting($sorting);
    $this->table->setPaginationFooter($this->getReq(), $pagination)->elementName('issues');
    
    $header = $this->table->setFilteringHeader($filtering);
    $header->addTextField('title')->label('Title');
    self::setupIssueTagOptions($header->addMultiSelect('type')->label('Type'), IssueType::list());
    self::setupIssueTagOptions($header->addMultiSelect('priority')->label('Priority'), IssuePriority::list());
    self::setupIssueTagOptions($header->addMultiSelect('scale')->label('Scale'), IssueScale::list());
    self::setupIssueTagOptions($header->addMultiSelect('status')->label('Status'), IssueStatus::list());
    
    $filtering_milestone = $header->addMultiSelect('milestone')->label('Milestone');
    $filtering_milestone->addOption('', Text::missing('None'));
    
    foreach((new MilestoneTable(DB::get(), $project))->listMilestones() as $milestone){
      $filtering_milestone->addOption(strval($milestone->getMilestoneId()), Text::plain($milestone->getTitle()));
    }
    
    // TODO get rid of IDs and allow filtering by manually typing username (either add a field, or just in the URL & add the options if the user cannot see everyone)
    // TODO could also have a way of including former members
    
    $filtering_author = $header->addMultiSelect('author')->label('Author');
    $filtering_author->addOption('', Text::missing('Nobody'));
    
    $filtering_assignee = $header->addMultiSelect('assignee')->label('Assignee');
    $filtering_assignee->addOption('', Text::missing('Nobody'));
    
    if ($logon_user_id !== null){
      $filtering_author->addOption(strval($logon_user_id), Text::missing('You'));
      $filtering_assignee->addOption(strval($logon_user_id), Text::missing('You'));
      
      if ($this->perms->check(ProjectPermissions::LIST_MEMBERS)){
        foreach((new ProjectMemberTable(DB::get(), $project))->listMembers() as $member){
          $user_id = $member->getUserId();
          
          if ($user_id !== $logon_user_id){
            $user_id_str = strval($user_id);
            $user_name = $member->getUserName();
            
            $filtering_author->addOption($user_id_str, Text::plain($user_name));
            $filtering_assignee->addOption($user_id_str, Text::plain($user_name));
          }
        }
      }
    }
    
    if ($this->perms->check(ProjectPermissions::CREATE_ISSUE)){
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
