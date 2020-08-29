<?php
declare(strict_types = 1);

namespace Pages\Models\Mixed;

use Data\UserId;
use Database\DB;
use Database\Objects\ProjectInfo;
use Database\Tables\UserTable;
use Database\Validation\UserFields;
use Exception;
use LogicException;
use Pages\Components\Forms\FormComponent;
use Pages\Models\BasicMixedPageModel;
use Routing\Request;
use Session\Session;
use Validation\FormValidator;
use Validation\ValidationException;

class RegisterModel extends BasicMixedPageModel{
  public const ACTION_REGISTER = 'Register';
  
  private FormComponent $form;
  private bool $successful_login;
  
  public function __construct(Request $req, ?ProjectInfo $project, bool $successful_login = false){
    parent::__construct($req, $project);
    
    $this->form = new FormComponent(self::ACTION_REGISTER);
    
    $this->form->addTextField('Name')
               ->label('Username')
               ->type('text')
               ->autocomplete('username');
    
    $this->form->addTextField('Password')
               ->type('password')
               ->autocomplete('new-password');
    
    $this->form->addTextField('PasswordRepeated')
               ->label('Confirm Password')
               ->type('password')
               ->autocomplete('new-password');
    
    $this->form->addTextField('Email')
               ->type('email')
               ->autocomplete('email');
    
    $this->form->addButton('submit', 'Register')
               ->icon('pencil');
    
    $this->successful_login = $successful_login;
  }
  
  public function getForm(): FormComponent{
    return $this->form;
  }
  
  public function isSuccessfulLogin(): bool{
    return $this->successful_login;
  }
  
  public function registerUser(array $data, Session $sess): bool{
    if (!$this->form->accept($data)){
      return false;
    }
    
    $validator = new FormValidator($data);
    $name = UserFields::name($validator);
    $email = UserFields::email($validator);
    $password = UserFields::password($validator);
    $validator->str('PasswordRepeated')->isTrue(fn($v): bool => $v === $password, 'Passwords do not match.')->val();
    
    try{
      $validator->validate();
      
      if (self::checkDuplicateUser($this->form, $name, $email)){
        return false;
      }
      
      $users = new UserTable(DB::get());
      $users->addUser($name, $email, $password);
      
      if ($sess->tryLoginWithName($name)){
        return true;
      }
      else{
        throw new LogicException('Could not login a newly registered user.');
      }
    }catch(ValidationException $e){
      $this->form->invalidateFields($e->getFields());
    }catch(Exception $e){
      $this->form->onGeneralError($e);
    }
    
    return false;
  }
  
  public static function checkDuplicateUser(FormComponent $form, string $name, string $email, ?UserId $exclude_id = null): bool{
    try{
      $users = new UserTable(DB::get());
      $has_duplicate = false;
      
      $name_match = $users->findIdByName($name);
      $email_match = $users->findIdByEmail($email);
      
      if ($name_match !== null && !$name_match->equals($exclude_id)){
        $form->invalidateField('Name', 'User with this name already exists.');
        $has_duplicate = true;
      }
      
      if ($email_match !== null && !$email_match->equals($exclude_id)){
        $form->invalidateField('Email', 'User with this email already exists.');
        $has_duplicate = true;
      }
      
      if ($has_duplicate){
        return true;
      }
    }catch(Exception $e){
      $form->onGeneralError($e);
    }
    
    return false;
  }
}

?>
