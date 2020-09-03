<?php
declare(strict_types = 1);

namespace Database\Validation;

use Validation\FormValidator;

final class RoleFields{
  public static function title(FormValidator $validator): string{
    return $validator->str('Title')->notEmpty()->maxLength(32)->val();
  }
  
  public static function permissionFieldName(string $perm): string{
    return 'Perm-'.str_replace('.', '_', $perm);
  }
  
  public static function permissions(FormValidator $validator, array $perm_names, array $perm_deps): array{
    $fields = [];
    $checked_perms = [];
    
    foreach(array_keys($perm_names) as $perm){
      $field = $validator->bool(self::permissionFieldName($perm));
      $fields[$perm] = $field;
      
      $value = $field->val();
      
      if ($value){
        $checked_perms[] = $perm;
      }
    }
    
    foreach($checked_perms as $perm){
      $dependency = $perm_deps[$perm] ?? null;
      
      if ($dependency !== null && !in_array($dependency, $checked_perms, true)){
        $fields[$perm]->isTrue(fn($ignore): bool => in_array($dependency, $checked_perms, true), 'This permission requires the \''.$perm_names[$dependency].'\' permission.');
      }
    }
    
    return $checked_perms;
  }
}

?>
