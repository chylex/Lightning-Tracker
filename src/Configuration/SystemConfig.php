<?php
declare(strict_types = 1);

namespace Configuration;

use Validation\ValidationException;
use Validation\Validator;

final class SystemConfig{
  private static function validateProtocol(string $url): bool{
    return mb_substr($url, 0, 7) === 'http://' || mb_substr($url, 0, 8) === 'https://';
  }
  
  private string $sys_enable_registration;
  private string $base_url;
  private string $db_name;
  private string $db_host;
  private string $db_user;
  private string $db_password;
  
  public function __construct(array $data){
    $this->sys_enable_registration = ($data['SysEnableRegistration'] ?? false) ? 'true' : 'false';
    $this->base_url = rtrim($data['BaseUrl'], '/');
    $this->db_name = $data['DbName'];
    $this->db_host = $data['DbHost'];
    $this->db_user = $data['DbUser'];
    $this->db_password = $data['DbPassword'];
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
    $sys_enable_registration = $this->sys_enable_registration;
    $base_url = addcslashes($this->base_url, '\'\\');
    $db_name = addcslashes($this->db_name, '\'\\');
    $db_host = addcslashes($this->db_host, '\'\\');
    $db_user = addcslashes($this->db_user, '\'\\');
    $db_password = addcslashes($this->db_password, '\'\\');
    
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
    
    return $contents;
  }
}

?>
