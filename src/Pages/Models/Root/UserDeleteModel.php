<?php
declare(strict_types = 1);

namespace Pages\Models\Root;

use Database\DB;
use Database\Objects\ProjectInfo;
use Database\Objects\UserInfo;
use Database\Objects\UserStatistics;
use Database\Tables\ProjectTable;
use Database\Tables\UserTable;
use Pages\Components\Forms\FormComponent;
use Pages\IModel;
use Pages\Models\BasicRootPageModel;
use Routing\Request;

class UserDeleteModel extends BasicRootPageModel{
  public const ACTION_CONFIRM = 'Confirm';
  
  private int $user_id;
  private ?UserInfo $user;
  
  private FormComponent $form;
  
  /**
   * @var ProjectInfo[]
   */
  private array $owned_projects = [];
  
  private UserStatistics $statistics;
  
  public function __construct(Request $req, int $user_id){
    parent::__construct($req);
    $this->user_id = $user_id;
    
    $users = new UserTable(DB::get());
    $this->user = $users->getUserInfo($user_id);
    
    $this->form = new FormComponent(self::ACTION_CONFIRM);
    $this->form->addTextField('Name')->label('Username');
    $this->form->addButton('submit', 'Delete User')->icon('trash');
  }
  
  public function load(): IModel{
    parent::load();
    
    if ($this->user !== null){
      foreach((new ProjectTable(DB::get()))->listProjectsOwnedBy($this->user_id) as $project){
        $this->owned_projects[] = $project;
      }
      
      $this->statistics = (new UserTable(DB::get()))->getUserStatistics($this->user_id);
    }
    
    return $this;
  }
  
  public function getUser(): ?UserInfo{
    return $this->user;
  }
  
  public function getDeleteForm(): FormComponent{
    return $this->form;
  }
  
  public function canDelete(): bool{
    return $this->user === null || !$this->user->isAdmin(); // null allows page to be shown instead of error message
  }
  
  public function getOwnedProjects(): array{
    return $this->owned_projects;
  }
  
  public function getStatistics(): UserStatistics{
    return $this->statistics;
  }
  
  public function deleteUser(array $data): bool{
    if (!$this->form->accept($data)){
      return false;
    }
    
    $confirmation = $data['Name'] ?? null;
    
    if ($confirmation !== $this->user->getName()){
      $this->form->invalidateField('Name', 'Incorrect username.');
      return false;
    }
    
    $users = new UserTable(DB::get());
    $users->deleteById($this->user_id);
    return true;
  }
}

?>
