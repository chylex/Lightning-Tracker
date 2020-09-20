<?php
declare(strict_types = 1);

namespace Pages\Views\Root;

use Database\Objects\RoleInfo;
use Pages\Components\CompositeComponent;
use Pages\Components\SplitComponent;
use Pages\Components\Table\TableComponent;
use Pages\Components\Text;
use Pages\Components\TitledSectionComponent;
use Pages\IViewable;
use Pages\Models\Root\SettingsRolesModel;
use Routing\Link;

class SettingsRolesPage extends AbstractSettingsPage{
  private SettingsRolesModel $model;
  
  public function __construct(SettingsRolesModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getSubtitle(): string{
    return parent::getSubtitle().' - Roles';
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_FULL;
  }
  
  protected function echoPageHead(): void{
    parent::echoPageHead();
    TableComponent::echoHead();
  }
  
  protected function getSettingsPageColumn(): IViewable{
    $split = new SplitComponent(75);
    $split->collapseAt(1024, true);
    $split->setRightWidthLimits(250, 500);
    
    $split->addLeft($this->createRoleTable());
    $split->addRight(new TitledSectionComponent('Create Role', $this->model->getCreateForm()));
    
    return $split;
  }
  
  private function createRoleTable(): TableComponent{
    $req = $this->model->getReq();
    
    $table = new TableComponent();
    $table->ifEmpty('No roles found.');
    
    $table->addColumn('Title')->width(20)->bold();
    $table->addColumn('Permissions')->width(80)->wrap();
    $table->addColumn('Actions')->tight()->right();
    
    foreach($this->model->getRoles() as $info){
      $role = $info->getRole();
      
      switch($role->getType()){
        case RoleInfo::SYSTEM_ADMIN:
          $perm_list_str = Text::missing('All');
          break;
        
        default:
          $perm_list = implode(', ', array_map(static fn($perm): string => SettingsRolesModel::PERM_NAMES[$perm], $info->getPerms()));
          $perm_list_str = empty($perm_list) ? Text::missing('None') : $perm_list;
          break;
      }
      
      $row = [$role->getTitleSafe(),
              $perm_list_str,
              CompositeComponent::nonNull($this->model->createMoveForm($info), $this->model->createDeleteForm($role))];
      
      $row = $table->addRow($row);
      
      if ($this->model->canEditRole($role)){
        $row->link(Link::fromBase($req, 'settings', 'roles', (string)($role->getId())));
      }
    }
    
    return $table;
  }
}

?>
