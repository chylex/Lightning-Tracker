<?php
declare(strict_types = 1);

namespace Pages\Components;

use Pages\IViewable;

final class SplitComponent implements IViewable{
  public static function echoHead(): void{
    $v = TRACKER_RESOURCE_VERSION;
    
    echo <<<HTML
<link rel="stylesheet" type="text/css" href="~resources/css/split.css?v=$v">
HTML;
  }
  
  private int $left_width_percentage;
  private ?array $left_width_limits = null;
  private ?array $right_width_limits = null;
  private ?int $collapse_at = null;
  private bool $collapse_reversed = false;
  
  /**
   * @var IViewable[]
   */
  private array $left = [];
  
  /**
   * @var IViewable[]
   */
  private array $right = [];
  
  public function __construct(int $left_width_percentage){
    $this->left_width_percentage = $left_width_percentage;
  }
  
  public function setLeftWidthLimits(int $min_width, ?int $max_width = null): self{
    $this->left_width_limits = [$min_width, $max_width];
    return $this;
  }
  
  public function setRightWidthLimits(int $min_width, ?int $max_width = null): self{
    $this->right_width_limits = [$min_width, $max_width];
    return $this;
  }
  
  public function collapseAt(int $width, bool $reversed = false): self{
    $this->collapse_at = $width;
    $this->collapse_reversed = $reversed;
    return $this;
  }
  
  public function addLeft(IViewable $component): self{
    $this->left[] = $component;
    return $this;
  }
  
  public function addRight(IViewable $component): self{
    $this->right[] = $component;
    return $this;
  }
  
  public function addLeftIfNotNull(?IViewable $component): self{
    if ($component !== null){
      $this->left[] = $component;
    }
    
    return $this;
  }
  
  public function addRightIfNotNull(?IViewable $component): self{
    if ($component !== null){
      $this->right[] = $component;
    }
    
    return $this;
  }
  
  private function getWidthLimitClass(?array $limits): string{
    if ($limits === null){
      return '';
    }
    elseif ($limits[1] === null){
      return ' min-width-'.$limits[0];
    }
    else{
      return ' min-width-'.$limits[0].' max-width-'.$limits[1];
    }
  }
  
  public function echoBody(): void{
    $collapse_class = $this->collapse_at === null ? '' : ' split-collapse-'.$this->collapse_at.($this->collapse_reversed ? ' split-collapse-reversed' : '');
    
    echo '<div class="split-wrapper'.$collapse_class.'">';
    
    if (empty($this->left)){
      echo '<div class="split-100">';
      $this->echoRight();
      echo '</div>';
    }
    elseif (empty($this->right)){
      echo '<div class="split-100">';
      $this->echoLeft();
      echo '</div>';
    }
    else{
      echo '<div class="split-'.$this->left_width_percentage.$this->getWidthLimitClass($this->left_width_limits).'">';
      $this->echoLeft();
      echo '</div>';
      
      echo '<div class="split-'.(100 - $this->left_width_percentage).$this->getWidthLimitClass($this->right_width_limits).'">';
      $this->echoRight();
      echo '</div>';
    }
    
    echo '</div>';
  }
  
  private function echoLeft(): void{
    foreach($this->left as $component){
      $component->echoBody();
    }
  }
  
  private function echoRight(): void{
    foreach($this->right as $component){
      $component->echoBody();
    }
  }
}

?>
