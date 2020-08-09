<?php
declare(strict_types = 1);

namespace Database\Filters;

class Pagination{
  public static function empty(): self{
    return new self(1, 0, 1);
  }
  
  public static function fromGet(string $key, int $total_elements, int $elements_per_page): self{
    return new self((int)($_GET[$key] ?? 1), $total_elements, $elements_per_page);
  }
  
  private int $current_page;
  private int $total_pages;
  
  private int $total_elements;
  private int $elements_per_page;
  
  public function __construct(int $current_page, int $total_elements, int $elements_per_page){
    $this->total_pages = (int)ceil((float)$total_elements / $elements_per_page);
    $this->current_page = max(1, min($this->total_pages, $current_page));
    
    $this->total_elements = $total_elements;
    $this->elements_per_page = $elements_per_page;
  }
  
  public function getCurrentPage(): int{
    return $this->current_page;
  }
  
  public function getTotalPages(): int{
    return $this->total_pages;
  }
  
  public function getTotalElements(): int{
    return $this->total_elements;
  }
  
  public function getElementsPerPage(): int{
    return $this->elements_per_page;
  }
}

?>
