<?php
declare(strict_types = 1);

namespace Pages\Components\Table\Elements;

use Database\Filters\General\Pagination;
use Pages\IViewable;
use Routing\Link;
use Routing\Request;

final class TablePaginationFooterComponent implements IViewable{
  private const PAGES_SHOWN = 15;
  
  private Request $req;
  private Pagination $pagination;
  
  private string $element_name = 'rows';
  
  public function __construct(Request $req, Pagination $pagination){
    $this->req = $req;
    $this->pagination = $pagination;
  }
  
  public function elementName(string $element_name): self{
    $this->element_name = $element_name;
    return $this;
  }
  
  /** @noinspection HtmlMissingClosingTag */
  public function echoBody(): void{
    $current_page = $this->pagination->getCurrentPage();
    $total_elements = $this->pagination->getTotalElements();
    $total_pages = $this->pagination->getTotalPages();
    $elements_per_page = $this->pagination->getElementsPerPage();
    
    if ($total_elements === 0){
      return;
    }
    
    $ele_1 = 1 + ($current_page - 1) * $elements_per_page;
    $ele_2 = min($ele_1 + $elements_per_page - 1, $total_elements);
    
    echo <<<HTML
<div class="pagination">
  <ul class="pages">
HTML;
    
    $this->echoPageIcon(1, 'backward');
    
    $page_1 = max(1, $current_page - (int)floor(self::PAGES_SHOWN / 2));
    $page_2 = min($page_1 + self::PAGES_SHOWN - 1, $total_pages);
    
    for($page = $page_1; $page <= $page_2; $page++){
      $this->echoPageNumber($page, (string)$page);
    }
    
    $this->echoPageIcon($total_pages, 'forward');
    
    echo <<<HTML
  </ul>
  <p class="element-info">
    Showing $this->element_name $ele_1 to $ele_2 out of $total_elements.
  </p>
</div>
HTML;
  }
  
  private function echoPageNumber(int $page, string $text): void{
    $active = $this->pagination->getCurrentPage() === $page ? ' class="active"' : '';
    $link = Link::withGet($this->req, Pagination::GET_PAGE, $page);
    
    echo <<<HTML
<li$active>
  <a href="$link">$text</a>
</li>
HTML;
  }
  
  private function echoPageIcon(int $page, string $icon): void{
    $link = Link::withGet($this->req, Pagination::GET_PAGE, $page);
    
    echo <<<HTML
<li>
  <a href="$link">
    <span class="icon icon-$icon"></span>
  </a>
</li>
HTML;
  }
}

?>
