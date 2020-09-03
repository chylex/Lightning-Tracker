<?php
declare(strict_types = 1);

use Configuration\ConfigFile;
use Data\UserId;
use Data\UserPassword;
use Database\DB;
use Routing\UrlString;
use Validation\ValidationException;

// Constants

$action_key = '_Action';
$action_value_install = '';
$action_value_conflict_confirm = 'ConflictConfirm';
$action_value_conflict_cancel = 'ConflictCancel';

$conflict_resolution_key = '_Resolution';
$conflict_resolution_delete = 'delete';
$conflict_resolution_reuse = 'reuse';

// Preparation

$https_header = $_SERVER['HTTPS'] ?? '';
$base_protocol = (!empty($https_header) && $https_header !== 'off' ? 'https' : 'http').'://';
$base_path = new UrlString($_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']);

$base_url_raw = $base_protocol.$base_path->raw();
$base_url_encoded = $base_protocol.$base_path->encoded();

$submit_action = $_POST[$action_key] ?? $action_value_install;
$conflict_action = $submit_action === $action_value_conflict_confirm ? ($_POST[$conflict_resolution_key] ?? '') : '';

if ($submit_action === $action_value_conflict_confirm && (
        $conflict_action !== $conflict_resolution_delete &&
        $conflict_action !== $conflict_resolution_reuse)
){
  $submit_action = $action_value_conflict_cancel;
  $conflict_action = '';
}

$form_section_install = true;
$form_section_conflict = false;

$value_base_url = $_POST['BaseUrl'] ?? $base_url_raw;
$value_sys_enable_registration = (bool)($_POST['SysEnableRegistration'] ?? 'on');
$value_admin_name = $_POST['AdminName'] ?? '';
$value_admin_password = $_POST['AdminPassword'] ?? '';
$value_admin_password_repeated = $_POST['AdminPasswordRepeated'] ?? '';
$value_admin_email = $_POST['AdminEmail'] ?? '';
$value_db_name = $_POST['DbName'] ?? '';
$value_db_host = $_POST['DbHost'] ?? '';
$value_db_user = $_POST['DbUser'] ?? '';
$value_db_password = $_POST['DbPassword'] ?? '';

$errors = [];
$conflicts = [];

// Installation

if (!empty($_POST) && $submit_action !== $action_value_conflict_cancel){
  $config = ConfigFile::fromForm($_POST);
  
  try{
    $config->validate();
  }catch(ValidationException $e){
    foreach($e->getFields() as $field){
      $errors[] = $field->getMessage();
    }
  }
  
  if (empty($value_db_password)){
    $errors[] = 'Database password must not be empty.';
  }
  
  if (empty($value_admin_name)){
    $errors[] = 'Administrator account name must not be empty.';
  }
  
  if (strlen($value_admin_password) < 7){
    $errors[] = 'Administrator password must be at least 7 characters long.';
  }
  elseif (strlen($value_admin_password) > 72){
    $errors[] = 'Administrator password must be at most 72 characters long.';
  }
  elseif ($value_admin_password !== $value_admin_password_repeated){
    $errors[] = 'Administrator passwords do not match.';
  }
  
  if (mb_strpos($value_admin_email, '@') === false){
    $errors[] = 'Administrator email is invalid.';
  }
  
  // Database Connection Test
  
  define('DB_DRIVER', 'mysql');
  define('DB_NAME', $value_db_name);
  define('DB_HOST', $value_db_host);
  define('DB_USER', $value_db_user);
  define('DB_PASSWORD', $value_db_password);
  
  $db = null;
  
  if (empty($errors)){
    try{
      $db = DB::get();
    }catch(PDOException $e){
      $code = (string)$e->getCode();
      
      if ($code === '2002'){
        $errors[] = 'Database error - server did not respond.';
      }
      elseif ($code === '1044' || $code === '1045' || $code === '1049'){
        $errors[] = 'Database error - invalid credentials or database name.';
      }
      else{
        $errors[] = 'Database error - unknown error, code '.$code.'.';
      }
    }
  }
  
  // Database Engine Check
  
  if (empty($errors)){
    try{
      $stmt = $db->query('SHOW ENGINES');
      $stmt->execute();
      $engines = $stmt->fetchAll();
      
      $supports_innodb = false;
      
      foreach($engines as $info){
        if ($info['Engine'] === 'InnoDB'){
          $supports_innodb = $info['Support'] === 'DEFAULT' || $info['Support'] === 'YES';
          break;
        }
      }
      
      if (!$supports_innodb){
        $errors[] = 'Database system does not support the InnoDB engine.';
      }
    }catch(PDOException $e){
      $errors[] = 'Could not detect database engines.';
    }
  }
  
  // Previous Installation / Table Conflict Detection
  
  $any_table_existed = false;
  
  if (empty($errors) && $submit_action === $action_value_install){
    $tests = [
        'users'      => ['user', 'users'],
        'projects'   => ['project', 'projects'],
        'milestones' => ['milestone', 'milestones'],
        'issues'     => ['issue', 'issues']
    ];
    
    foreach($tests as $table => $data){
      try{
        $stmt = $db->query('SELECT COUNT(*) FROM '.$table);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        $stmt->closeCursor();
        
        $any_table_existed = true;
        $conflicts[] = [$count === false ? 0 : (int)$count, $data[0], $data[1]];
      }catch(PDOException $e){
        // ignore
      }
    }
    
    if ($any_table_existed){
      $form_section_install = false;
      $form_section_conflict = true;
      $errors[] = 'A previous installation of Lightning Tracker already exists.';
    }
  }
  
  // Database Setup
  
  if (empty($errors)){
    /**
     * @param string $path
     * @return string
     * @throws Exception
     */
    function read_sql_file(string $path): string{
      $file = __DIR__.'/~database/'.$path;
      $contents = file_get_contents($file);
      
      if ($contents === false){
        throw new Exception('Error reading file \''.$path.'\'.');
      }
      
      return $contents;
    }
    
    $tables = [
        'SystemRole'          => 'system_roles',
        'SystemRolePerm'      => 'system_role_perms',
        'User'                => 'users',
        'UserLogin'           => 'user_logins',
        'Project'             => 'projects',
        'ProjectRole'         => 'project_roles',
        'ProjectRolePerm'     => 'project_role_perms',
        'ProjectMember'       => 'project_members',
        'Milestone'           => 'milestones',
        'IssueWeight'         => 'issue_weights',
        'Issue'               => 'issues',
        'ProjectUserSettings' => 'project_user_settings',
    ];
    
    $values = [
        'IssueWeight'
    ];
    
    try{
      if ($conflict_action === $conflict_resolution_delete){
        foreach(array_reverse($tables, true) as $file => $table){
          $db->exec('DROP TABLE '.$table);
        }
      }
      
      foreach($tables as $file => $table){
        $db->exec(read_sql_file($file.'Table.sql'));
      }
      
      foreach($values as $file){
        $db->exec(read_sql_file($file.'Values.sql'));
      }
    }catch(PDOException $e){
      $errors[] = 'Error setting up database: '.$e->getMessage();
    }catch(Exception $e){
      $errors[] = 'Error reading database setup files: '.$e->getMessage();
    }
  }
  
  // Administrator Account
  
  if (empty($errors) && $conflict_action !== $conflict_resolution_reuse){
    try{
      $stmt = $db->prepare('INSERT INTO users (id, name, email, password, admin, date_registered) VALUES (?, ?, ?, ?, TRUE, NOW())');
      $stmt->bindValue(1, UserId::generateNew());
      $stmt->bindValue(2, $value_admin_name);
      $stmt->bindValue(3, $value_admin_email);
      $stmt->bindValue(4, UserPassword::hash($value_admin_password));
      $stmt->execute();
    }catch(Exception $e){
      $errors[] = 'Error setting up administrator account: '.$e->getMessage();
    }
  }
  
  // Configuration File
  
  if (empty($errors) && !$config->write(CONFIG_FILE)){
    $errors[] = 'Error creating \'config.php\'.';
  }
  
  if (empty($errors)){
    header('Location: '.$base_url_encoded);
  }
}

// Page Layout

$value_base_url = protect($value_base_url);
$value_admin_name = protect($value_admin_name);
$value_admin_password = protect($value_admin_password);
$value_admin_password_repeated = protect($value_admin_password_repeated);
$value_admin_email = protect($value_admin_email);
$value_db_name = protect($value_db_name);
$value_db_host = protect($value_db_host);
$value_db_user = protect($value_db_user);
$value_db_password = protect($value_db_password);

$sys_enable_registration_checked_attr = $value_sys_enable_registration ? ' checked' : '';

$error_str = implode('', array_map(fn($v): string => '<p class="message error">'.$v.'</p>', $errors));
$conflict_str = implode('', array_map(fn($v): string => '<li>'.$v[0].' '.($v[0] === 1 ? $v[1] : $v[2]).'</li>', $conflicts));

$form_section_install_style = $form_section_install ? '' : ' style="display:none"';
$form_section_conflict_style = $form_section_conflict ? '' : ' style="display:none"';

$form_section_install_button = $form_section_install ? 'submit' : 'button';
$form_section_conflict_button = $form_section_conflict ? 'submit' : 'button';

$v = TRACKER_RESOURCE_VERSION;

echo <<<HTML
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <title>Lightning Tracker - Install</title>
    
    <base href="$base_url_encoded/">
    <link rel="icon" type="image/png" href="~resources/img/favicon.png?v=$v">
    <link rel="stylesheet" type="text/css" href="~resources/style.min.css?v=$v">
  </head>
  <body>
    <main id="page-content" class="condensed">
      
      <h2>Lightning Tracker</h2>
      <form action="" method="post">
        
        <div id="form-install-section" $form_section_install_style>
          <div class="split-wrapper split-collapse-800">
            <div class="split-33">
              
              <h3>Site</h3>
              <article>
                <div class="field-group">
                  <label for="BaseUrl">Base URL</label>
                  <input id="BaseUrl" name="BaseUrl" type="text" value="$value_base_url">
                </div>
              </article>
              
              <h3>System</h3>
              <article>
                <div class="field-group">
                  <input id="SysEnableRegistration" name="SysEnableRegistration" type="checkbox" value="on" $sys_enable_registration_checked_attr>
                  <label for="SysEnableRegistration">Enable User Registration</label>
                </div>
              </article>
            
            </div>
            <div class="split-33">
              
              <h3>Your Account</h3>
              <article>
                <div class="field-group">
                  <label for="AdminName">Name</label>
                  <input id="AdminName" name="AdminName" type="text" value="$value_admin_name">
                </div>
                <div class="field-group">
                  <label for="AdminPassword">Password</label>
                  <input id="AdminPassword" name="AdminPassword" type="password" autocomplete="new-password" value="$value_admin_password">
                </div>
                <div class="field-group">
                  <label for="AdminPasswordRepeated">Confirm Password</label>
                  <input id="AdminPasswordRepeated" name="AdminPasswordRepeated" type="password" autocomplete="new-password" value="$value_admin_password_repeated">
                </div>
                <div class="field-group">
                  <label for="AdminEmail">Email</label>
                  <input id="AdminEmail" name="AdminEmail" type="email" value="$value_admin_email">
                </div>
              </article>
            
            </div>
            <div class="split-33">
              
              <h3>Database</h3>
              <article>
                <div class="field-group">
                  <label for="DbName">Name</label>
                  <input id="DbName" name="DbName" type="text" value="$value_db_name">
                </div>
                <div class="field-group">
                  <label for="DbHost">Host</label>
                  <input id="DbHost" name="DbHost" type="text" value="$value_db_host">
                </div>
                <div class="field-group">
                  <label for="DbUser">User</label>
                  <input id="DbUser" name="DbUser" type="text" value="$value_db_user">
                </div>
                <div class="field-group">
                  <label for="DbPassword">Password</label>
                  <input id="DbPassword" name="DbPassword" type="password" autocomplete="new-password" value="$value_db_password">
                </div>
              </article>
            
            </div>
          </div>
          
          <h3>Confirm</h3>
          <article>
            $error_str
            <button class="styled" type="$form_section_install_button" name="$action_key" value="$action_value_install">
              <span class="icon icon-wand"></span> Install Lightning Tracker
            </button>
          </article>
        </div>
        
        <div id="form-conflict-section" $form_section_conflict_style>
          <h3>Previous Installation Detected</h3>
          <article>
            <p>It appears Lightning Tracker (or another application with conflicting database tables) was already installed in this database.</p>
            
            <p>If you decide to <strong>delete</strong> the existing installation, you will lose:</p>
            <ul>
              $conflict_str
            </ul>
            
            <p>If you decide to <strong>reuse</strong> the existing installation, the following will happen:</p>
            <ul>
              <li>The installation will only add missing tables and update tables containing system-wide constants.</li>
              <li>The installation will not create a new administrator account. You will need to login using an existing administrator account.</li>
              <li>If the existing installation was made with a different version of Lightning Tracker, it may not work correctly.</li>
            </ul>
            
            <p>Please type the desired action (<strong>delete</strong> or <strong>reuse</strong>) to proceed, or leave the field empty to go back:</p>
            <div class="field-group">
              <input name="$conflict_resolution_key" type="text" value="" pattern="delete|reuse">
            </div>
            <button class="styled" type="$form_section_conflict_button" name="$action_key" value="$action_value_conflict_confirm">
              <span class="icon icon-warning"></span> Confirm
            </button>
            <button class="styled" type="$form_section_conflict_button" name="$action_key" value="$action_value_conflict_cancel">
              <span class="icon icon-blocked"></span> Cancel
            </button>
          </article>
        </div>
      
      </form>
    
    </main>
  </body>
</html>
HTML;

?>
