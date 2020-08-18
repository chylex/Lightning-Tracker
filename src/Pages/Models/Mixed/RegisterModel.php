<?php
declare(strict_types = 1);

namespace Pages\Models\Mixed;

use Database\DB;
use Database\Objects\TrackerInfo;
use Database\SQL;
use Database\Tables\UserTable;
use Exception;
use LogicException;
use Pages\Components\Forms\FormComponent;
use Pages\Models\BasicMixedPageModel;
use PDOException;
use Routing\Request;
use Session\Session;
use Validation\ValidationException;
use Validation\Validator;

class RegisterModel extends BasicMixedPageModel{
  private FormComponent $form;
  private bool $successful_login;
  
  public function __construct(Request $req, ?TrackerInfo $tracker, bool $successful_login = false){
    parent::__construct($req, $tracker);
    
    $this->form = new FormComponent();
    
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
    
    $name = $data['Name'];
    $email = $data['Email'];
    $password = $data['Password'];
    $password_repeated = $data['PasswordRepeated'];
    
    $validator = self::validateUserFields($name, $email, $password);
    $validator->str('PasswordRepeated', $password_repeated)->isTrue(fn($v): bool => $v === $password, 'Passwords do not match.');
    
    try{
      $validator->validate();
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
    }catch(PDOException $e){
      if ($e->getCode() === SQL::CONSTRAINT_VIOLATION && self::checkDuplicateUser($this->form, $name, $email)){
        return false;
      }
      
      $this->form->onGeneralError($e);
    }catch(Exception $e){
      $this->form->onGeneralError($e);
    }
    
    return false;
  }
  
  public static function validateUserFields(string $name, string $email, ?string $password): Validator{
    $validator = new Validator();
    $validator->str('Name', $name)->notEmpty()->maxLength(32);
    $validator->str('Email', $email)->notEmpty()->maxLength(191)->contains('@', 'Email is not valid.');
    
    if ($password !== null){
      $validator->str('Password', $password)->minLength(7)->maxLength(72);
    }
    
    return $validator;
  }
  
  public static function checkDuplicateUser(FormComponent $form, string $name, string $email, ?int $exclude_id = null): bool{
    try{
      $users = new UserTable(DB::get());
      $has_duplicate = false;
      
      $name_match = $users->findIdByName($name);
      $email_match = $users->findIdByEmail($email);
      
      if ($name_match !== null && ($exclude_id === null || $exclude_id !== $name_match)){
        $form->invalidateField('Name', 'User with this name already exists.');
        $has_duplicate = true;
      }
      
      if ($email_match !== null && ($exclude_id === null || $exclude_id !== $email_match)){
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
