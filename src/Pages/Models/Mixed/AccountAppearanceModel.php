<?php
declare(strict_types = 1);

namespace Pages\Models\Mixed;

use Database\Filters\General\Pagination;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Text;
use Validation\FormValidator;
use Validation\ValidationException;

class AccountAppearanceModel extends AccountModel{
  public const ACTION_CHANGE_APPEARANCE = 'ChangeAppearance';
  
  private FormComponent $appearance_settings_form;
  
  public function getAppearanceSettingsForm(): FormComponent{
    if (isset($this->appearance_settings_form)){
      return $this->appearance_settings_form;
    }
    
    $form = new FormComponent(self::ACTION_CHANGE_APPEARANCE);
    $form->addHTML('<p>These settings are saved in this browser, they will not be synchronized across all of your devices.</p>');
    $form->setMessagePlacementHere();
    
    $form->addNumberField('TablePaginationElements', Pagination::MIN_ELEMENTS_PER_PAGE, 50)
         ->value((string)(int)($_COOKIE[Pagination::COOKIE_ELEMENTS] ?? Pagination::DEFAULT_ELEMENTS_PER_PAGE))
         ->label('Table Pagination - Elements Per Page');
    
    $form->addButton('submit', 'Update Appearance')
         ->icon('pencil');
    
    return $this->appearance_settings_form = $form;
  }
  
  public function updateAppearanceSettings(array $data): bool{
    $form = $this->getAppearanceSettingsForm();
    
    if (!$form->accept($data)){
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
      
      $form->addMessage(FormComponent::MESSAGE_SUCCESS, Text::checkmark('Settings were changed.'));
      return true;
    }catch(ValidationException $e){
      $form->invalidateFields($e->getFields());
      return false;
    }
  }
}

?>
