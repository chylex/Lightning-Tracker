<?php
declare(strict_types = 1);

namespace Database\Filters\General;

final class Pagination{
  public const GET_PAGE = 'page';
  public const COOKIE_ELEMENTS = 'pagination_elements';
  
  public const DEFAULT_ELEMENTS_PER_PAGE = 15;
  public const MIN_ELEMENTS_PER_PAGE = 5;
  
  public static function empty(): self{
    return new self(1, 0, 1);
  }
  
  public static function fromGlobals(int $total_elements): self{
    $current_page = (int)($_GET[self::GET_PAGE] ?? 1);
    $elements_per_page = (int)($_COOKIE[self::COOKIE_ELEMENTS] ?? self::DEFAULT_ELEMENTS_PER_PAGE);
    
    return new self($current_page, $total_elements, max($elements_per_page, self::MIN_ELEMENTS_PER_PAGE));
  }
  
  private int $current_page;
  private int $total_pages;
  
  private int $total_elements;
  private int $elements_per_page;
  
  private function __construct(int $current_page, int $total_elements, int $elements_per_page){
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
