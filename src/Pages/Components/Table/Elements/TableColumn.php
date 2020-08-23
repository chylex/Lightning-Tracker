<?php
declare(strict_types = 1);

namespace Pages\Components\Table\Elements;

use Database\Filters\General\Sorting;
use Pages\IViewable;

final class TableColumn implements IViewable{
  private string $title;
  private ?Sorting $sorting = null;
  private ?string $sort_key = null;
  private ?int $width_percentage = null;
  private ?string $align = null;
  private bool $wrap = false;
  private bool $collapsed = false;
  private bool $bold = false;
  
  public function __construct(string $title){
    $this->title = $title;
  }
  
  public function sort(?string $sort_key = null): self{
    $this->sort_key = $sort_key ?? $this->title;
    return $this;
  }
  
  public function width(int $width_percentage): self{
    $this->width_percentage = $width_percentage;
    return $this;
  }
  
  public function tight(): self{
    $this->width_percentage = 0;
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
  
  public function wrap(): self{
    $this->wrap = true;
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
  
  public function setupSorting(Sorting $sorting): void{
    if ($this->sort_key !== null && $sorting->isSortable($this->sort_key)){
      $this->sorting = $sorting;
    }
  }
  
  private function getClassAttr(): string{
    $classes = [];
    
    if ($this->align !== null){
      $classes[] = $this->align;
    }
    
    if ($this->wrap){
      $classes[] = 'wrap';
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
    
    if ($this->sorting === null){
      echo $this->title;
    }
    else{
      $sort_cycle_link = $this->sorting->generateCycledLink($this->sort_key);
      $sort_order = $this->sorting->getSortDirection($this->sort_key);
      $sort_icon = $sort_order === Sorting::SQL_ASC ? 'sort-asc' : ($sort_order === Sorting::SQL_DESC ? 'sort-desc' : 'sort-default');
      
      echo '<a href="'.$sort_cycle_link.'">';
      echo $this->title;
      echo ' <span class="sort '.$sort_icon.'"></span></a>';
    }
    
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
