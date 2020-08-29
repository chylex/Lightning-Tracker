<?php
declare(strict_types = 1);

namespace Database\Validation;

use Data\IssuePriority;
use Data\IssueScale;
use Data\IssueStatus;
use Data\IssueType;
use Validation\FormValidator;

final class IssueFields{
  public static function title(FormValidator $validator): string{
    return $validator->str('Title')->notEmpty()->maxLength(128)->val();
  }
  
  public static function description(FormValidator $validator): string{
    return $validator->str('Description')->maxLength(65000)->val();
  }
  
  public static function type(FormValidator $validator): IssueType{
    return IssueType::get($validator->str('Type')->isTrue(fn($v): bool => IssueType::exists($v), 'Please select a type.')->val());
  }
  
  public static function priority(FormValidator $validator): IssuePriority{
    return IssuePriority::get($validator->str('Priority')->isTrue(fn($v): bool => IssuePriority::exists($v), 'Invalid priority.')->val());
  }
  
  public static function scale(FormValidator $validator): IssueScale{
    return IssueScale::get($validator->str('Scale')->isTrue(fn($v): bool => IssueScale::exists($v), 'Invalid scale.')->val());
  }
  
  public static function status(FormValidator $validator): IssueStatus{
    return IssueStatus::get($validator->str('Status')->isTrue(fn($v): bool => IssueStatus::exists($v), 'Invalid status.')->val());
  }
  
  public static function progress(FormValidator $validator): int{
    return $validator->int('Progress')->min(0)->max(100)->val();
  }
}

?>
