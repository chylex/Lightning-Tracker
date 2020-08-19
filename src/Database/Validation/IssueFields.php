<?php
declare(strict_types = 1);

namespace Database\Validation;

use Pages\Components\Issues\IssuePriority;
use Pages\Components\Issues\IssueScale;
use Pages\Components\Issues\IssueStatus;
use Pages\Components\Issues\IssueType;
use Validation\FormValidator;

final class IssueFields{
  public static function title(FormValidator $validator): string{
    return $validator->str('Title')->notEmpty()->maxLength(128)->val();
  }
  
  public static function description(FormValidator $validator): string{
    return $validator->str('Description')->maxLength(65000)->val();
  }
  
  public static function type(FormValidator $validator): IssueType{
    return IssueType::get($validator->str('Type')->isTrue(fn($v): bool => IssueType::exists($v), 'Type is invalid.')->val());
  }
  
  public static function priority(FormValidator $validator): IssuePriority{
    return IssuePriority::get($validator->str('Priority')->isTrue(fn($v): bool => IssuePriority::exists($v), 'Priority is invalid.')->val());
  }
  
  public static function scale(FormValidator $validator): IssueScale{
    return IssueScale::get($validator->str('Scale')->isTrue(fn($v): bool => IssueScale::exists($v), 'Scale is invalid.')->val());
  }
  
  public static function status(FormValidator $validator): IssueStatus{
    return IssueStatus::get($validator->str('Status')->isTrue(fn($v): bool => IssueStatus::exists($v), 'Status is invalid.')->val());
  }
  
  public static function progress(FormValidator $validator): int{
    return $validator->int('Progress')->min(0)->max(100)->val();
  }
}

?>
