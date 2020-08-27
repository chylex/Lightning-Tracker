<?php
declare(strict_types = 1);

namespace Pages\Models\Mixed;

use Database\Filters\General\Pagination;
use Database\Objects\ProjectInfo;
use Database\Objects\UserProfile;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Text;
use Routing\Request;
use Validation\FormValidator;
use Validation\ValidationException;

class AccountAppearanceModel extends AccountModel{
  public const ACTION_CHANGE_APPEARANCE = 'ChangeAppearance';
  
  private FormComponent $appearance_form;
  
  public function __construct(Request $req, UserProfile $logon_user, ?ProjectInfo $project){
    parent::__construct($req, $logon_user, $project);
    
    $form = new FormComponent(self::ACTION_CHANGE_APPEARANCE);
    $form->startTitledSection('Appearance');
    $form->addHTML('<p>These settings are saved in this browser, they will not be synchronized across all of your devices.</p>');
    $form->setMessagePlacementHere();
    
    $form->addNumberField('TablePaginationElements', Pagination::MIN_ELEMENTS_PER_PAGE, 50)
         ->value((string)(int)($_COOKIE[Pagination::COOKIE_ELEMENTS] ?? Pagination::DEFAULT_ELEMENTS_PER_PAGE))
         ->label('Table Pagination - Elements Per Page');
    
    $form->addButton('submit', 'Update Appearance')
         ->icon('pencil');
    
    $form->endTitledSection();
    $this->appearance_form = $form;
  }
  
  public function getAppearanceSettingsForm(): FormComponent{
    return $this->appearance_form;
  }
  
  public function updateAppearanceSettings(array $data): bool{
    if (!$this->appearance_form->accept($data)){
      return false;
    }
    
    $validator = new FormValidator($data);
    $table_pagination_elements = $validator->int('TablePaginationElements', 'Elements per page')->min(5)->max(50)->val();
    
    try{
      $validator->validate();
      
      $path = BASE_PATH_ENC;
      $cookie = Pagination::COOKIE_ELEMENTS;
      $age = 0x7FFFFFFF;
      header("Set-Cookie: $cookie=$table_pagination_elements; Max-Age=$age; Path=$path/; SameSite=Lax");
      
      $this->appearance_form->addMessage(FormComponent::MESSAGE_SUCCESS, Text::checkmark('Settings were changed.'));
      return true;
    }catch(ValidationException $e){
      $this->appearance_form->invalidateFields($e->getFields());
      return false;
    }
  }
}

?>
