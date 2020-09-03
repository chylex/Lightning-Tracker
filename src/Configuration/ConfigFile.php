<?php
declare(strict_types = 1);

namespace Configuration;

use Validation\ValidationException;
use Validation\Validator;

final class ConfigFile{
  private static function validateProtocol(string $url): bool{
    return mb_substr($url, 0, 7) === 'http://' || mb_substr($url, 0, 8) === 'https://';
  }
  
  public static function fromForm(array $data): ConfigFile{
    return new ConfigFile((bool)($data['SysEnableRegistration'] ?? false),
                          rtrim($data['BaseUrl'], '/'),
                          $data['DbName'],
                          $data['DbHost'],
                          $data['DbUser'],
                          $data['DbPassword']);
  }
  
  public static function fromCurrentInstallation(): ConfigFile{
    return new ConfigFile(SYS_ENABLE_REGISTRATION, BASE_URL, DB_NAME, DB_HOST, DB_USER, DB_PASSWORD);
  }
  
  private bool $sys_enable_registration;
  private string $base_url;
  private string $db_name;
  private string $db_host;
  private string $db_user;
  private string $db_password;
  
  private function __construct(bool $sys_enable_registration, string $base_url, string $db_name, string $db_host, string $db_user, string $db_password){
    $this->sys_enable_registration = $sys_enable_registration;
    $this->base_url = $base_url;
    $this->db_name = $db_name;
    $this->db_host = $db_host;
    $this->db_user = $db_user;
    $this->db_password = $db_password;
  }
  
  /**
   * @throws ValidationException
   */
  public function validate(): void{
    $validator = new Validator();
    
    $validator->str('BaseUrl', $this->base_url)
              ->isTrue(fn($v): bool => filter_var(idn_to_ascii($this->base_url), FILTER_VALIDATE_URL) !== false, 'Base URL is not valid.')
              ->isTrue(fn($v): bool => self::validateProtocol($v), 'Base URL must specify either the HTTP or HTTPS protocol.');
    
    $validator->str('DbName', $this->db_name, 'Database name')->notEmpty();
    $validator->str('DbHost', $this->db_host, 'Database host')->notEmpty();
    $validator->str('DbUser', $this->db_user, 'Database user')->notEmpty();
    
    $validator->validate();
  }
  
  public function generate(): string{
    $sys_enable_registration = $this->sys_enable_registration ? 'true' : 'false';
    $base_url = addcslashes($this->base_url, '\'\\');
    $db_name = addcslashes($this->db_name, '\'\\');
    $db_host = addcslashes($this->db_host, '\'\\');
    $db_user = addcslashes($this->db_user, '\'\\');
    $db_password = addcslashes($this->db_password, '\'\\');
    
    /** @noinspection ALL */
    $contents = <<<PHP
<?php
declare(strict_types = 1);

define('SYS_ENABLE_REGISTRATION', $sys_enable_registration);
define('BASE_URL', '$base_url');

define('DB_DRIVER', 'mysql');
define('DB_NAME', '$db_name');
define('DB_HOST', '$db_host');
define('DB_USER', '$db_user');
define('DB_PASSWORD', '$db_password');
?>
PHP;
    
    return $contents;
  }
  
  public function write(string $file): bool{
    return file_put_contents($file, $this->generate(), LOCK_EX) !== false;
  }
}

?>
