<?php
declare(strict_types = 1);

namespace Pages\Models\Mixed;

use Database\DB;
use Database\Objects\TrackerInfo;
use Database\Tables\UserTable;
use Exception;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Text;
use Pages\Models\BasicMixedPageModel;
use Routing\Request;
use Session\Session;

class LoginModel extends BasicMixedPageModel{
  private FormComponent $form;
  
  public function __construct(Request $req, ?TrackerInfo $tracker){
    parent::__construct($req, $tracker);
    
    $this->form = new FormComponent();
    
    $this->form->addTextField('Name')
               ->label('Username')
               ->type('text')
               ->autocomplete('username');
    
    $this->form->addTextField('Password')
               ->type('password')
               ->autocomplete('current-password');
    
    $this->form->addButton('submit', 'Login')
               ->icon('enter');
  }
  
  public function getForm(): FormComponent{
    return $this->form;
  }
  
  public function loginUser(array $data, Session $sess): bool{
    if (!$this->form->accept($data)){
      return false;
    }
    
    try{
      $users = new UserTable(DB::get());
      $login_info = $users->getLoginInfo($data['Name']);
      
      if ($login_info === null || !$login_info->checkPassword($data['Password'])){
        $this->form->addMessage(FormComponent::MESSAGE_ERROR, Text::warning('Invalid username or password.'));
        return false;
      }
      
      return $sess->tryLoginWithId($login_info->getId());
    }catch(Exception $e){
      $this->form->onGeneralError($e);
      return false;
    }
  }
}

?>
