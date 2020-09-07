<?php
declare(strict_types = 1);

namespace Pages\Models\Root;

use Data\UserId;
use Database\DB;
use Database\Objects\ProjectInfo;
use Database\Objects\UserInfo;
use Database\Objects\UserStatistics;
use Database\Tables\ProjectTable;
use Database\Tables\UserTable;
use Pages\Components\Forms\FormComponent;
use Pages\Models\BasicRootPageModel;
use Routing\Request;

class UserDeleteModel extends BasicRootPageModel{
  public const ACTION_CONFIRM = 'Confirm';
  
  private UserId $user_id;
  private ?UserInfo $user;
  private bool $can_delete = false;
  
  private FormComponent $delete_form;
  
  public function __construct(Request $req, UserId $user_id, UserId $logon_user_id){
    parent::__construct($req);
    $this->user_id = $user_id;
    $this->user = (new UserTable(DB::get()))->getUserInfo($user_id);
    
    if ($this->user !== null){
      $this->can_delete = UserEditModel::canEditUser($logon_user_id, $this->user);
    }
  }
  
  public function canDelete(): bool{
    return $this->can_delete;
  }
  
  public function getUser(): ?UserInfo{
    return $this->user;
  }
  
  /**
   * @return ProjectInfo[]
   */
  public function getOwnedProjects(): array{
    return (new ProjectTable(DB::get()))->listProjectsOwnedBy($this->user_id);
  }
  
  public function getStatistics(): UserStatistics{
    return (new UserTable(DB::get()))->getUserStatistics($this->user_id);
  }
  
  public function getDeleteForm(): FormComponent{
    if (isset($this->delete_form)){
      return $this->delete_form;
    }
    
    $form = new FormComponent(self::ACTION_CONFIRM);
    $form->addTextField('Name')->label('Username');
    $form->addButton('submit', 'Delete User')->icon('trash');
    
    return $this->delete_form = $form;
  }
  
  public function deleteUser(array $data): bool{
    $form = $this->getDeleteForm();
    
    if (!$form->accept($data)){
      return false;
    }
    
    $confirmation = $data['Name'] ?? null;
    
    if ($confirmation !== $this->user->getName()){
      $form->invalidateField('Name', 'Incorrect username.');
      return false;
    }
    
    $users = new UserTable(DB::get());
    $users->deleteById($this->user_id);
    return true;
  }
}

?>
