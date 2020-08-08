<?php
declare(strict_types = 1);

namespace Pages\Models\Root;

use Pages\Components\Forms\FormComponent;
use Pages\Components\Text;
use Pages\Models\BasicRootPageModel;
use Routing\Request;
use Validation\ValidationException;
use Validation\Validator;
use function Database\protect;

class SettingsModel extends BasicRootPageModel{
  private const ROOT_FOLDER = __DIR__.'/../../../';
  private const CONFIG_FILE = self::ROOT_FOLDER.'config.php';
  private const BACKUP_FILE = self::ROOT_FOLDER.'config.old.php';
  
  public const ACTION_UPDATE_SETTINGS = 'UpdateSettings';
  public const ACTION_REMOVE_BACKUP = 'RemoveBackup';
  
  public const PERM = 'settings';
  
  private static function validateProtocol(string $url): bool{
    return mb_substr($url, 0, 7) === 'http://' || mb_substr($url, 0, 8) === 'https://';
  }
  
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
    $this->form->addTextField('BaseUrl')->label('Base URL')->value(protect(BASE_URL));
    $this->form->endTitledSection();
    
    $this->form->addHTML(<<<HTML
  </div>
  <div class="split-50">
HTML
    );
    
    $this->form->startTitledSection('Database');
    $this->form->addTextField('DbName')->label('Name')->value(protect(DB_NAME));
    $this->form->addTextField('DbHost')->label('Host')->value(protect(DB_HOST));
    $this->form->addTextField('DbUser')->label('User')->value(protect(DB_USER));
    $this->form->addTextField('DbPassword')->label('Password')->type('password')->autocomplete('new-password')->value(protect(DB_PASSWORD));
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
    
    $this->form->addButton('submit', 'Update Settings')->value(self::ACTION_UPDATE_SETTINGS)->icon('pencil');
    
    if (file_exists(self::BACKUP_FILE)){
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
    if (@unlink(self::BACKUP_FILE)){
      $this->form->addMessage(FormComponent::MESSAGE_SUCCESS, Text::checkmark('Backup file removed.'));
      return true;
    }
    else{
      $this->form->addMessage(FormComponent::MESSAGE_ERROR, Text::warning('Backup file could not be removed.'));
      return false;
    }
  }
  
  public function updateConfig(array $data): bool{
    $sys_enable_registration = ($data['SysEnableRegistration'] ?? false) ? 'true' : 'false';
    $base_url = rtrim($data['BaseUrl'], '/');
    $db_name = $data['DbName'];
    $db_host = $data['DbHost'];
    $db_user = $data['DbUser'];
    $db_password = $data['DbPassword'];
    
    $validator = new Validator();
    
    $validator->str('BaseUrl', $base_url)
              ->isTrue(fn($v): bool => filter_var(idn_to_ascii($base_url), FILTER_VALIDATE_URL) !== false, 'Base URL is not valid.')
              ->isTrue(fn($v): bool => self::validateProtocol($v), 'Base URL must specify either the HTTP or HTTPS protocol.');
    
    $validator->str('DbName', $db_name, 'Name')->notEmpty();
    $validator->str('DbHost', $db_host, 'Host')->notEmpty();
    $validator->str('DbUser', $db_user, 'User')->notEmpty();
    
    try{
      $validator->validate();
    }catch(ValidationException $e){
      $this->form->invalidateFields($e->getFields());
      return false;
    }
    
    if (!copy(self::CONFIG_FILE, self::BACKUP_FILE)){
      $this->form->addMessage(FormComponent::MESSAGE_ERROR, Text::warning('Error creating backup of \'config.php\'.'));
      return false;
    }
    
    $base_url = addcslashes($base_url, '\'\\');
    $db_name = addcslashes($db_name, '\'\\');
    $db_host = addcslashes($db_host, '\'\\');
    $db_user = addcslashes($db_user, '\'\\');
    $db_password = addcslashes($db_password, '\'\\');
    
    /** @noinspection ALL */
    $contents = <<<PHP
<?php
define('SYS_ENABLE_REGISTRATION', $sys_enable_registration);

define('BASE_URL', '$base_url');

define('DB_DRIVER', 'mysql');
define('DB_NAME', '$db_name');
define('DB_HOST', '$db_host');
define('DB_USER', '$db_user');
define('DB_PASSWORD', '$db_password');
?>
PHP;
    
    if (!file_put_contents(self::CONFIG_FILE, $contents, LOCK_EX)){
      $this->form->addMessage(FormComponent::MESSAGE_ERROR, Text::warning('Error updating \'config.php\'.'));
      return false;
    }
    
    $this->form->addMessage(FormComponent::MESSAGE_SUCCESS, Text::checkmark('Configuration was updated.'));
    return true;
  }
}

?>
