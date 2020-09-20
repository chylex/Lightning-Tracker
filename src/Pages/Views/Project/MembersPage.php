<?php
declare(strict_types = 1);

namespace Pages\Views\Project;

use Pages\Components\Forms\FormComponent;
use Pages\Components\Forms\IconButtonFormComponent;
use Pages\Components\SplitComponent;
use Pages\Components\Table\TableComponent;
use Pages\Components\Text;
use Pages\Components\TitledSectionComponent;
use Pages\Models\Project\MembersModel;
use Pages\Views\AbstractProjectPage;
use Routing\Link;

class MembersPage extends AbstractProjectPage{
  private MembersModel $model;
  
  public function __construct(MembersModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getHeading(): string{
    return 'Members';
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_FULL;
  }
  
  protected function echoPageHead(): void{
    SplitComponent::echoHead();
    TableComponent::echoHead();
    FormComponent::echoHead();
  }
  
  protected function echoPageBody(): void{
    $split = new SplitComponent(75);
    $split->collapseAt(800, true);
    $split->setRightWidthLimits(250, 400);
    
    $split->addLeft($this->createMemberTable());
    $split->addRightIfNotNull(TitledSectionComponent::wrap('Invite User', $this->model->getInviteForm()));
    
    $split->echoBody();
  }
  
  private function createMemberTable(): TableComponent{
    $req = $this->model->getReq();
    $can_manage_members = $this->model->canManageMembers();
    
    $table = new TableComponent();
    $table->ifEmpty('No members found.');
    
    $table->addColumn('Username')->sort('name')->width(60)->wrap()->bold();
    $table->addColumn('Role')->sort('role_order')->width(40);
    
    if ($can_manage_members){
      $table->addColumn('Actions')->right()->tight();
    }
    
    $filter = $this->model->setupProjectMemberFilter($table);
    
    foreach($this->model->getMemberList($filter) as $member){
      /** @noinspection ProperNullCoalescingOperatorUsageInspection */
      $row = [$member->getUserNameSafe(),
              $member->getRoleTitleSafe() ?? Text::missing('Default')];
      
      $user_id = $member->getUserId();
      $user_id_str = $user_id->formatted();
      
      $can_edit = $this->model->canEditMember($member);
      
      if ($can_manage_members){
        if ($can_edit){
          $link_delete = Link::fromBase($req, 'members', $user_id_str, 'remove');
          $btn_delete = new IconButtonFormComponent($link_delete, 'circle-cross');
          $btn_delete->color('red');
          $row[] = $btn_delete;
        }
        else{
          $row[] = '';
        }
      }
      
      $row = $table->addRow($row);
      
      if ($can_edit){
        $row->link(Link::fromBase($req, 'members', $user_id_str));
      }
    }
    
    return $table;
  }
}

?>
