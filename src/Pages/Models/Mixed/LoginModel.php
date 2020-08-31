<?php
declare(strict_types = 1);

namespace Pages\Models\Mixed;

use Database\DB;
use Database\Tables\UserTable;
use Exception;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Text;
use Pages\Models\BasicMixedPageModel;
use Session\Session;

class LoginModel extends BasicMixedPageModel{
  public const ACTION_LOGIN = 'Login';
  
  private FormComponent $login_form;
  
  public function getLoginForm(): FormComponent{
    if (isset($this->login_form)){
      return $this->login_form;
    }
    
    $form = new FormComponent(self::ACTION_LOGIN);
    
    $form->addTextField('Name')
         ->label('Username')
         ->type('text')
         ->autocomplete('username');
    
    $form->addTextField('Password')
         ->type('password')
         ->autocomplete('current-password');
    
    $form->addButton('submit', 'Login')
         ->icon('enter');
    
    return $this->login_form = $form;
  }
  
  public function loginUser(array $data, Session $sess): bool{
    $form = $this->getLoginForm();
    
    if (!$form->accept($data)){
      return false;
    }
    
    try{
      $users = new UserTable(DB::get());
      $login_info = $users->getLoginInfo($data['Name']);
      
      if ($login_info === null || !$login_info->getPassword()->check($data['Password'])){
        $form->addMessage(FormComponent::MESSAGE_ERROR, Text::blocked('Invalid username or password.'));
        return false;
      }
      
      return $sess->tryLoginWithId($login_info->getId());
    }catch(Exception $e){
      $form->onGeneralError($e);
      return false;
    }
  }
}

?>
