<?php
declare(strict_types = 1);

namespace Pages\Models\Mixed;

use Database\DB;
use Database\Objects\ProjectInfo;
use Database\Objects\UserProfile;
use Database\Tables\UserTable;
use Database\Validation\UserFields;
use Exception;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Text;
use Routing\Request;
use Validation\FormValidator;
use Validation\ValidationException;

class AccountSecurityModel extends AccountModel{
  public const ACTION_CHANGE_PASSWORD = 'ChangePassword';
  
  private FormComponent $change_password_form;
  
  public function __construct(Request $req, UserProfile $logon_user, ?ProjectInfo $project){
    parent::__construct($req, $logon_user, $project);
    
    $form = new FormComponent(self::ACTION_CHANGE_PASSWORD);
    $form->startTitledSection('Change Password');
    $form->setMessagePlacementHere();
    
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
    
    $form->endTitledSection();
    $this->change_password_form = $form;
  }
  
  public function getChangePasswordForm(): FormComponent{
    return $this->change_password_form;
  }
  
  public function changePassword(array $data): bool{
    if (!$this->change_password_form->accept($data)){
      return false;
    }
    
    $validator = new FormValidator($data);
    $new_password = UserFields::password($validator, 'NewPassword');
    $validator->str('NewPasswordRepeated')->isTrue(fn($v): bool => $v === $new_password, 'Passwords do not match.');
    
    $user = $this->getLogonUser();
    
    try{
      $users = new UserTable(DB::get());
      $login_info = $users->getLoginInfo($user->getName());
      
      if ($login_info === null){
        $this->change_password_form->onGeneralError(new Exception('User does not exist.'));
        return false;
      }
      
      $validator->str('OldPassword')->isTrue(fn($v): bool => $login_info->getPassword()->check($v), 'Password does not match.');
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
