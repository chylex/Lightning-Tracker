<?php
declare(strict_types = 1);

namespace Pages\Views\Root;

use Database\Objects\RoleInfo;
use Pages\Components\DateTimeComponent;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Forms\IconButtonFormComponent;
use Pages\Components\SplitComponent;
use Pages\Components\Table\TableComponent;
use Pages\Components\Text;
use Pages\Components\TitledSectionComponent;
use Pages\Models\Root\UsersModel;
use Pages\Views\AbstractPage;
use Routing\Link;

class UsersPage extends AbstractPage{
  private UsersModel $model;
  
  public function __construct(UsersModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getHeading(): string{
    return 'Users';
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_FULL;
  }
  
  protected function echoPageHead(): void{
    SplitComponent::echoHead();
    TableComponent::echoHead();
    FormComponent::echoHead();
    DateTimeComponent::echoHead();
  }
  
  protected function echoPageBody(): void{
    $split = new SplitComponent(75);
    $split->collapseAt(800, true);
    $split->setRightWidthLimits(250, 500);
    
    $split->addLeft($this->createUserTable());
    $split->addRightIfNotNull(TitledSectionComponent::wrap('Create User', $this->model->getCreateForm()));
    
    $split->echoBody();
  }
  
  private function createUserTable(): TableComponent{
    $req = $this->model->getReq();
    $can_see_email = $this->model->canSeeEmail();
    $can_manage_users = $this->model->canManageUsers();
    
    $table = new TableComponent();
    $table->ifEmpty('No users found.');
    
    if ($can_see_email){
      $table->addColumn('Username')->sort('name')->width(40)->wrap()->bold();
      $table->addColumn('Email')->width(40)->wrap();
    }
    else{
      $table->addColumn('Username')->sort('name')->width(80)->wrap()->bold();
    }
    
    $table->addColumn('Role')->sort('role_order')->width(20);
    $table->addColumn('Registration Time')->sort('date_registered')->tight()->right();
    
    if ($can_manage_users){
      $table->addColumn('Actions')->tight()->right();
    }
    
    $filter = $this->model->setupUserTableFilter($table);
    
    foreach($this->model->getUserList($filter) as $user){
      $user_id = $user->getId();
      $user_id_formatted = $user_id->formatted();
      
      $row = [$user->getNameSafe()];
      
      if ($can_see_email){
        $row[] = $user->getEmailSafe();
      }
      
      switch($user->getRoleType()){
        case RoleInfo::SYSTEM_ADMIN:
          $row[] = Text::missing('Admin');
          break;
        
        default:
          /** @noinspection ProperNullCoalescingOperatorUsageInspection */
          $row[] = $user->getRoleTitleSafe() ?? Text::missing('Default');
          break;
      }
      
      $row[] = new DateTimeComponent($user->getRegistrationDate());
      
      $can_edit = $this->model->canEditUser($user);
      
      if ($can_manage_users){
        if ($can_edit){
          $link_delete = Link::fromBase($req, 'users', $user_id_formatted, 'delete');
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
        $row->link(Link::fromBase($req, 'users', $user_id_formatted));
      }
    }
    
    return $table;
  }
}

?>
