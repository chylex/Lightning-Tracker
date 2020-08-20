<?php
declare(strict_types = 1);

namespace Pages\Models\Tracker;

use Database\DB;
use Database\Filters\Types\IssueFilter;
use Database\Objects\TrackerInfo;
use Database\Tables\IssueTable;
use Database\Tables\MilestoneTable;
use Database\Tables\TrackerMemberTable;
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
use Pages\Models\BasicTrackerPageModel;
use Routing\Link;
use Routing\Request;
use Session\Permissions;
use Session\Session;

class IssuesModel extends BasicTrackerPageModel{
  public const PERM_CREATE = 'issues.create';
  public const PERM_EDIT_ALL = 'issues.edit.all';
  public const PERM_DELETE_ALL = 'issues.delete.all';
  
  /**
   * @param FormSelectMultiple $select
   * @param IIssueTag[] $items
   */
  private static function setupIssueTagOptions(FormSelectMultiple $select, array $items): void{
    foreach($items as $item){
      $select->addOption($item->getId(), new Html('<span class="'.$item->getTagClass().'"></span> '.$item->getTitle()));
    }
  }
  
  private Permissions $perms;
  private TableComponent $table;
  private SidemenuComponent $menu_actions;
  
  public function __construct(Request $req, TrackerInfo $tracker, Permissions $perms){
    parent::__construct($req, $tracker);
    
    $this->perms = $perms;
    
    $this->table = new TableComponent();
    $this->table->ifEmpty('No issues found.');
    
    $this->table->addColumn('')->tight()->collapsed();
    $this->table->addColumn('ID')->sort('id')->tight()->collapsed()->right()->bold();
    $this->table->addColumn('Title')->sort('title')->width(70)->collapsed()->bold();
    $this->table->addColumn('Priority')->sort('priority')->tight();
    $this->table->addColumn('Scale')->sort('scale')->tight();
    $this->table->addColumn('Status')->tight();
    $this->table->addColumn('Progress')->sort('progress')->width(30);
    $this->table->addColumn('Last Update')->sort('date_updated')->tight();
    
    $this->menu_actions = new SidemenuComponent($req);
    $this->menu_actions->setTitle(Text::plain('Actions'));
  }
  
  public function load(): IModel{
    parent::load();
    
    $tracker = $this->getTracker();
    $logon_user_id = Session::get()->getLogonUserId();
    
    $filter = new IssueFilter();
    $issues = new IssueTable(DB::get(), $tracker);
    
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
    $filtering_milestone->addOption('', Text::missing('(No Milestone)'));
    
    foreach((new MilestoneTable(DB::get(), $tracker))->listMilestones() as $milestone){
      $filtering_milestone->addOption(strval($milestone->getMilestoneId()), Text::plain($milestone->getTitle()));
    }
    
    // TODO get rid of IDs and allow filtering by manually typing username (either add a field, or just in the URL & add the options if the user cannot see everyone)
    // TODO could also have a way of including former members
    
    $filtering_author = $header->addMultiSelect('author')->label('Author');
    $filtering_author->addOption('', Text::missing('(No Author)'));
    
    $filtering_assignee = $header->addMultiSelect('assignee')->label('Assignee');
    $filtering_assignee->addOption('', Text::missing('(No Assignee)'));
    
    if ($logon_user_id !== null){
      $filtering_author->addOption(strval($logon_user_id), Text::missing('(You)'));
      $filtering_assignee->addOption(strval($logon_user_id), Text::missing('(You)'));
      
      if ($this->perms->checkTracker($tracker, MembersModel::PERM_LIST)){
        foreach((new TrackerMemberTable(DB::get(), $tracker))->listMembers() as $member){
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
