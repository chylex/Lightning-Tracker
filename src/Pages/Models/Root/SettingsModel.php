<?php
declare(strict_types = 1);

namespace Pages\Models\Root;

use Configuration\SystemConfig;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Text;
use Pages\Models\BasicRootPageModel;
use Routing\Request;
use Validation\ValidationException;

class SettingsModel extends BasicRootPageModel{
  public const ACTION_UPDATE_SETTINGS = 'UpdateSettings';
  public const ACTION_REMOVE_BACKUP = 'RemoveBackup';
  
  public const PERM = 'settings';
  
  private FormComponent $form;
  
  /** @noinspection HtmlMissingClosingTag */
  public function __construct(Request $req){
    parent::__construct($req);
    
    $this->form = new FormComponent();
    $this->form->addHTML(<<<HTML
<div class="split-wrapper split-collapse-640">
  <div class="split-50">
HTML
    );
    
    $this->form->startTitledSection('System');
    $this->form->addCheckBox('SysEnableRegistration')->label('Enable User Registration')->value(SYS_ENABLE_REGISTRATION);
    $this->form->endTitledSection();
    
    $this->form->startTitledSection('Site');
    $this->form->addTextField('BaseUrl')->label('Base URL')->value(BASE_URL);
    $this->form->endTitledSection();
    
    $this->form->addHTML(<<<HTML
  </div>
  <div class="split-50">
HTML
    );
    
    $this->form->startTitledSection('Database');
    $this->form->addTextField('DbName')->label('Name')->value(DB_NAME);
    $this->form->addTextField('DbHost')->label('Host')->value(DB_HOST);
    $this->form->addTextField('DbUser')->label('User')->value(DB_USER);
    $this->form->addTextField('DbPassword')->label('Password')->type('password')->autocomplete('new-password')->value(DB_PASSWORD);
    $this->form->endTitledSection();
    
    $this->form->addHTML(<<<HTML
  </div>
</div>
<h3>Confirm</h3>
<article>
  <p>Warning: Changing these settings will re-generate 'config.php' in the installation folder.
     If certain settings are incorrect, you will not be able to access this page again without manual intervention.
     Current configuration file will be backed up as 'config.old.php'.</p>
HTML
    );
  
    $this->form->setMessagePlacementHere();
    $this->form->addButton('submit', 'Update Settings')->value(self::ACTION_UPDATE_SETTINGS)->icon('pencil');
    
    if (file_exists(CONFIG_BACKUP_FILE)){
      $this->form->addButton('submit', 'Remove Backup File')->value(self::ACTION_REMOVE_BACKUP)->icon('trash');
    }
    
    echo <<<HTML
</article>
HTML;
  }
  
  public function getForm(): FormComponent{
    return $this->form;
  }
  
  public function removeBackupFile(): bool{
    if (@unlink(CONFIG_BACKUP_FILE)){
      $this->form->addMessage(FormComponent::MESSAGE_SUCCESS, Text::checkmark('Backup file removed.'));
      return true;
    }
    else{
      $this->form->addMessage(FormComponent::MESSAGE_ERROR, Text::warning('Backup file could not be removed.'));
      return false;
    }
  }
  
  public function updateConfig(array $data): bool{
    $config = SystemConfig::fromForm($data);
    
    try{
      $config->validate();
    }catch(ValidationException $e){
      $this->form->invalidateFields($e->getFields());
      return false;
    }
    
    if (!copy(CONFIG_FILE, CONFIG_BACKUP_FILE)){
      $this->form->addMessage(FormComponent::MESSAGE_ERROR, Text::warning('Error creating backup of \'config.php\'.'));
      return false;
    }
    
    if (!file_put_contents(CONFIG_FILE, $config->generate(), LOCK_EX)){
      $this->form->addMessage(FormComponent::MESSAGE_ERROR, Text::warning('Error updating \'config.php\'.'));
      return false;
    }
    
    $this->form->addMessage(FormComponent::MESSAGE_SUCCESS, Text::checkmark('Configuration was updated.'));
    return true;
  }
}

?>
