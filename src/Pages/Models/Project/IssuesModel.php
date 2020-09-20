<?php
declare(strict_types = 1);

namespace Pages\Models\Project;

use Data\IssuePriority;
use Data\IssueScale;
use Data\IssueStatus;
use Data\IssueType;
use Database\DB;
use Database\Filters\Types\IssueFilter;
use Database\Objects\IssueInfo;
use Database\Objects\ProjectInfo;
use Database\Tables\IssueTable;
use Database\Tables\MilestoneTable;
use Pages\Components\Forms\Elements\FormSelectMultiple;
use Pages\Components\Html;
use Pages\Components\Issues\IIssueTag;
use Pages\Components\Sidemenu\SidemenuComponent;
use Pages\Components\Table\TableComponent;
use Pages\Components\Text;
use Pages\Models\BasicProjectPageModel;
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
      $menu->addLink(Text::withIcon('New Issue', 'pencil'), '/issues/new', 'New-Issue');
    }
    
    return $menu->getIfNotEmpty();
  }
  
  public function setupIssueTableFilter(TableComponent $table): IssueFilter{
    $req = $this->getReq();
    
    $filter = new IssueFilter();
    $issues = new IssueTable(DB::get(), $this->getProject());
    
    $filtering = $filter->filter();
    $total_count = $issues->countIssues($filter);
    $pagination = $filter->page($total_count);
    $sorting = $filter->sort($req);
    
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
    
    foreach((new MilestoneTable(DB::get(), $this->getProject()))->listMilestones() as $milestone){
      $filtering_milestone->addOption((string)$milestone->getMilestoneId(), Text::plain($milestone->getTitle()));
    }
    
    $filtering_author = $header->addMultiSelect('author')->label('Author');
    $filtering_author->addOption('', Text::missing('Nobody'));
    
    $filtering_assignee = $header->addMultiSelect('assignee')->label('Assignee');
    $filtering_assignee->addOption('', Text::missing('Nobody'));
    
    $logon_user_id = Session::get()->getLogonUserId();
    
    if ($logon_user_id !== null){
      $filtering_author->addOption($logon_user_id->raw(), Text::missing('You'));
      $filtering_assignee->addOption($logon_user_id->raw(), Text::missing('You'));
      
      $groups = [
          [$filtering_author, $issues->listAuthors()],
          [$filtering_assignee, $issues->listAssignees()],
      ];
      
      foreach($groups as [$select, $users]){
        foreach($users as $user){
          $user_id = $user->getId();
          
          if (!$user_id->equals($logon_user_id)){
            $select->addOption($user_id->raw(), Text::plain($user->getName()));
          }
        }
      }
    }
    
    return $filter;
  }
  
  /**
   * @param IssueFilter $filter
   * @return IssueInfo[]
   */
  public function getIssues(IssueFilter $filter): array{
    return (new IssueTable(DB::get(), $this->getProject()))->listIssues($filter);
  }
}

?>
