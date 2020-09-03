<?php
declare(strict_types = 1);

namespace Pages\Models\Root;

use Configuration\ConfigFile;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Text;
use Validation\ValidationException;

class SettingsGeneralModel extends AbstractSettingsModel{
  public const ACTION_SUBMIT = 'Submit';
  public const BUTTON_UPDATE_SETTINGS = 'UpdateSettings';
  public const BUTTON_REMOVE_BACKUP = 'RemoveBackup';
  
  private FormComponent $settings_form;
  
  /** @noinspection HtmlMissingClosingTag */
  public function getSettingsForm(): FormComponent{
    if (isset($this->settings_form)){
      return $this->settings_form;
    }
    
    $form = new FormComponent(self::ACTION_SUBMIT);
    $form->addHTML(<<<HTML
<div class="split-wrapper split-collapse-640">
  <div class="split-50">
HTML
    );
    
    $form->startTitledSection('System');
    $form->addCheckBox('SysEnableRegistration')->label('Enable User Registration')->value(SYS_ENABLE_REGISTRATION);
    $form->endTitledSection();
    
    $form->startTitledSection('Site');
    $form->addTextField('BaseUrl')->label('Base URL')->value(BASE_URL);
    $form->endTitledSection();
    
    $form->addHTML(<<<HTML
  </div>
  <div class="split-50">
HTML
    );
    
    $form->startTitledSection('Database');
    
    $form->addTextField('DbName')->label('Name')->value(DB_NAME);
    $form->addTextField('DbHost')->label('Host')->value(DB_HOST);
    $form->addTextField('DbUser')->label('User')->value(DB_USER);
    
    $form->addTextField('DbPassword')
         ->label('Password')
         ->type('password')
         ->autocomplete('new-password')
         ->placeholder('Leave blank to keep current password.');
    
    $form->endTitledSection();
    
    $form->addHTML(<<<HTML
  </div>
</div>
<h3>Confirm</h3>
<article>
  <p>Warning: Changing these settings will re-generate 'config.php' in the installation folder.
     If certain settings are incorrect, you will not be able to access this page again without manual intervention.
     Current configuration file will be backed up as 'config.old.php'.</p>
HTML
    );
    
    $form->setMessagePlacementHere();
    $form->addButton('submit', 'Update Settings')->value(self::BUTTON_UPDATE_SETTINGS)->icon('pencil');
    
    if (file_exists(CONFIG_BACKUP_FILE)){
      $form->addButton('submit', 'Remove Backup File')->value(self::BUTTON_REMOVE_BACKUP)->icon('trash');
    }
    
    echo <<<HTML
</article>
HTML;
    
    return $this->settings_form = $form;
  }
  
  public function removeBackupFile(array $data): bool{
    $form = $this->getSettingsForm();
    
    if (!$form->accept($data)){
      return false;
    }
    
    if (@unlink(CONFIG_BACKUP_FILE)){
      $form->addMessage(FormComponent::MESSAGE_SUCCESS, Text::checkmark('Backup file removed.'));
      return true;
    }
    else{
      $form->addMessage(FormComponent::MESSAGE_ERROR, Text::blocked('Backup file could not be removed.'));
      return false;
    }
  }
  
  public function updateConfig(array $data): bool{
    $form = $this->getSettingsForm();
    
    if (!$form->accept($data)){
      return false;
    }
    
    if (empty($data['DbPassword'])){
      $data['DbPassword'] = DB_PASSWORD;
    }
    
    $config = ConfigFile::fromForm($data);
    
    try{
      $config->validate();
    }catch(ValidationException $e){
      $form->invalidateFields($e->getFields());
      return false;
    }
    
    if (!copy(CONFIG_FILE, CONFIG_BACKUP_FILE)){
      $form->addMessage(FormComponent::MESSAGE_ERROR, Text::blocked('Error creating backup of \'config.php\'.'));
      return false;
    }
    
    if (!$config->write(CONFIG_FILE)){
      $form->addMessage(FormComponent::MESSAGE_ERROR, Text::blocked('Error updating \'config.php\'.'));
      return false;
    }
    
    $form->addMessage(FormComponent::MESSAGE_SUCCESS, Text::checkmark('Configuration was updated.'));
    return true;
  }
}

?>
