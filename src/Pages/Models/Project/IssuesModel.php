<?php
declare(strict_types = 1);

namespace Pages\Models\Project;

use Data\IssuePriority;
use Data\IssueScale;
use Data\IssueStatus;
use Data\IssueType;
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
use Pages\Components\ProgressBarComponent;
use Pages\Components\Sidemenu\SidemenuComponent;
use Pages\Components\Table\TableComponent;
use Pages\Components\Text;
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
  
  public function __construct(Request $req, ProjectInfo $project, ProjectPermissions $perms){
    parent::__construct($req, $project);
    $this->perms = $perms;
  }
  
  public function createMenuAction(): ?SidemenuComponent{
    $menu = new SidemenuComponent($this->getReq());
    $menu->setTitle(Text::plain('Actions'));
    
    if ($this->perms->check(ProjectPermissions::CREATE_ISSUE)){
      $menu->addLink(Text::withIcon('New Issue', 'pencil'), '/issues/new');
    }
    
    return $menu->getIfNotEmpty();
  }
  
  public function createIssueTable(): TableComponent{
    $req = $this->getReq();
    $project = $this->getProject();
    $logon_user_id = Session::get()->getLogonUserId();
    
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
    
    $filter = new IssueFilter();
    $issues = new IssueTable(DB::get(), $project);
    
    $filtering = $filter->filter();
    $total_count = $issues->countIssues($filter);
    $pagination = $filter->page($total_count);
    $sorting = $filter->sort($req);
    
    foreach($issues->listIssues($filter) as $issue){
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
    
    $table->setupColumnSorting($sorting);
    $table->setPaginationFooter($req, $pagination)->elementName('issues');
    
    $header = $table->setFilteringHeader($filtering);
    $header->addTextField('title')->label('Title');
    self::setupIssueTagOptions($header->addMultiSelect('type')->label('Type'), IssueType::list());
    self::setupIssueTagOptions($header->addMultiSelect('priority')->label('Priority'), IssuePriority::list());
    self::setupIssueTagOptions($header->addMultiSelect('scale')->label('Scale'), IssueScale::list());
    self::setupIssueTagOptions($header->addMultiSelect('status')->label('Status'), IssueStatus::list());
    
    $filtering_milestone = $header->addMultiSelect('milestone')->label('Milestone');
    $filtering_milestone->addOption('', Text::missing('None'));
    
    foreach((new MilestoneTable(DB::get(), $project))->listMilestones() as $milestone){
      $filtering_milestone->addOption((string)$milestone->getMilestoneId(), Text::plain($milestone->getTitle()));
    }
    
    // TODO get rid of IDs and allow filtering by manually typing username (either add a field, or just in the URL & add the options if the user cannot see everyone)
    // TODO could also have a way of including former members
    
    $filtering_author = $header->addMultiSelect('author')->label('Author');
    $filtering_author->addOption('', Text::missing('Nobody'));
    
    $filtering_assignee = $header->addMultiSelect('assignee')->label('Assignee');
    $filtering_assignee->addOption('', Text::missing('Nobody'));
    
    if ($logon_user_id !== null){
      $filtering_author->addOption($logon_user_id->raw(), Text::missing('You'));
      $filtering_assignee->addOption($logon_user_id->raw(), Text::missing('You'));
      
      if ($this->perms->check(ProjectPermissions::LIST_MEMBERS)){
        foreach((new ProjectMemberTable(DB::get(), $project))->listMembers() as $member){
          $user_id = $member->getUserId();
          
          if (!$user_id->equals($logon_user_id)){
            $user_id_str = $user_id->raw();
            $user_name = $member->getUserName();
            
            $filtering_author->addOption($user_id_str, Text::plain($user_name));
            $filtering_assignee->addOption($user_id_str, Text::plain($user_name));
          }
        }
      }
    }
    
    return $table;
  }
}

?>
