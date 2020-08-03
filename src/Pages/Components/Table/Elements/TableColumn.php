<?php
declare(strict_types = 1);

namespace Pages\Components\Table\Elements;

use Pages\IViewable;

class TableColumn implements IViewable{
  private string $title;
  private ?int $width_percentage = null;
  private ?string $align = null;
  private bool $collapsed = false;
  private bool $bold = false;
  
  public function __construct(string $title){
    $this->title = $title;
  }
  
  public function width(int $width_percentage): self{
    $this->width_percentage = $width_percentage;
    return $this;
  }
  
  public function center(): self{
    $this->align = 'center';
    return $this;
  }
  
  public function right(): self{
    $this->align = 'right';
    return $this;
  }
  
  public function tight(): self{
    $this->width_percentage = 0;
    return $this;
  }
  
  public function collapsed(): self{
    $this->collapsed = true;
    return $this;
  }
  
  public function bold(): self{
    $this->bold = true;
    return $this;
  }
  
  private function getClassAttr(): string{
    $classes = [];
    
    if ($this->align !== null){
      $classes[] = $this->align;
    }
    
    if ($this->collapsed){
      $classes[] = 'collapsed';
    }
    
    if ($this->bold){
      $classes[] = 'bold';
    }
    
    return empty($classes) ? '' : ' class="'.implode(' ', $classes).'"';
  }
  
  public function echoBody(): void{
    $perc = $this->width_percentage;
    $width = $perc === null ? '' : ($perc === 0 ? ' style="width:0px"' : ' width="'.$perc.'%"');
    
    echo '<th'.$width.$this->getClassAttr().'>';
    echo $this->title;
    echo '</th>';
  }
  
  public function echoCellStart(): void{
    echo '<td'.$this->getClassAttr().'>';
  }
  
  public function echoCellEnd(): void{
    echo '</td>';
  }
}

?>
