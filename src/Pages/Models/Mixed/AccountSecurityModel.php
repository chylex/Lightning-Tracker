<?php
declare(strict_types = 1);

namespace Pages\Models\Mixed;

use Database\DB;
use Database\Objects\TrackerInfo;
use Database\Objects\UserProfile;
use Database\Tables\UserTable;
use Exception;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Text;
use Routing\Request;
use Validation\ValidationException;
use Validation\Validator;

class AccountSecurityModel extends AccountModel{
  private FormComponent $change_password_form;
  
  public function __construct(Request $req, UserProfile $logon_user, ?TrackerInfo $tracker){
    parent::__construct($req, $logon_user, $tracker);
    
    $form = new FormComponent('ChangePassword');
    
    $form->addTextField('OldPassword')
         ->label('Current Password')
         ->type('password')
         ->autocomplete('current-password');
    
    $form->startSplitGroup(50);
    
    $form->addTextField('NewPassword')
         ->label('New Password')
         ->type('password')
         ->autocomplete('new-password');
    
    $form->addTextField('NewPasswordRepeated')
         ->label('Confirm New Password')
         ->type('password')
         ->autocomplete('new-password');
    
    $form->endSplitGroup();
    
    $form->addButton('submit', 'Change Password')
         ->icon('pencil');
    
    $this->change_password_form = $form;
  }
  
  public function getChangePasswordForm(): FormComponent{
    return $this->change_password_form;
  }
  
  public function changePassword(array $data): bool{
    if (!$this->change_password_form->accept($data)){
      return false;
    }
    
    $old_password = $data['OldPassword'];
    $new_password = $data['NewPassword'];
    $new_password_repeated = $data['NewPasswordRepeated'];
    
    $validator = new Validator();
    $validator->str('NewPassword', $new_password, 'Password')->minLength(7)->maxLength(72);
    $validator->str('NewPasswordRepeated', $new_password_repeated)->isTrue(fn($v): bool => $v === $new_password, 'Passwords do not match.');
    
    $user = $this->getLogonUser();
    
    try{
      $users = new UserTable(DB::get());
      $login_info = $users->getLoginInfo($user->getName());
      
      if ($login_info === null){
        $this->change_password_form->onGeneralError(new Exception('User does not exist.'));
        return false;
      }
      
      $validator->str('OldPassword', $old_password)->isTrue(fn($v): bool => $login_info->checkPassword($v), 'Password does not match.');
      $validator->validate();
      
      $users->changePassword($user->getId(), $new_password);
      
      $this->change_password_form->addMessage(FormComponent::MESSAGE_SUCCESS, Text::checkmark('Password was changed.'));
      return true;
    }catch(ValidationException $e){
      $this->change_password_form->invalidateFields($e->getFields());
    }catch(Exception $e){
      $this->change_password_form->onGeneralError($e);
    }
    
    return false;
  }
}

?>
