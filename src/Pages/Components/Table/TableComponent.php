<?php
declare(strict_types = 1);

namespace Pages\Components\Table;

use Database\Filters\General\Filtering;
use Database\Filters\General\Pagination;
use Database\Filters\General\Sorting;
use Pages\Components\Table\Elements\TableColumn;
use Pages\Components\Table\Elements\TableFilteringHeaderComponent;
use Pages\Components\Table\Elements\TablePaginationFooterComponent;
use Pages\Components\Table\Elements\TableRow;
use Pages\IViewable;
use Routing\Request;

final class TableComponent implements IViewable{
  public static function echoHead(): void{
    if (DEBUG){
      echo '<link rel="stylesheet" type="text/css" href="~resources/css/tables.css?v='.TRACKER_RESOURCE_VERSION.'">';
    }
    
    echo '<script type="text/javascript" src="~resources/js/tables.js?v='.TRACKER_RESOURCE_VERSION.'"></script>';
  }
  
  /**
   * @var TableColumn[]
   */
  private array $columns = [];
  
  /**
   * @var TableRow[]
   */
  private array $rows = [];
  
  private ?string $empty_html = null;
  private ?IViewable $header = null;
  private ?IViewable $footer = null;
  
  public function addColumn(string $title): TableColumn{
    $column = new TableColumn($title);
    $this->columns[] = $column;
    return $column;
  }
  
  /**
   * @param array $values Array of strings or IViewable elements.
   * @return TableRow
   */
  public function addRow(array $values): TableRow{
    $row = new TableRow($this->columns, $values);
    $this->rows[] = $row;
    return $row;
  }
  
  public function ifEmpty(string $empty_html): void{
    $this->empty_html = $empty_html;
  }
  
  public function setupColumnSorting(Sorting $sorting){
    foreach($this->columns as $column){
      $column->setupSorting($sorting);
    }
  }
  
  public function setFilteringHeader(Filtering $filtering): TableFilteringHeaderComponent{
    $this->header = new TableFilteringHeaderComponent($filtering);
    return $this->header;
  }
  
  public function setPaginationFooter(Request $req, Pagination $pagination): TablePaginationFooterComponent{
    $this->footer = new TablePaginationFooterComponent($req, $pagination);
    return $this->footer;
  }
  
  /** @noinspection HtmlMissingClosingTag */
  public function echoBody(): void{
    if ($this->header !== null){
      $this->header->echoBody();
    }
    
    echo <<<HTML
<table>
  <thead>
    <tr>
HTML;
    
    foreach($this->columns as $column){
      $column->echoBody();
    }
    
    echo <<<HTML
    </tr>
  </thead>
  <tbody>
HTML;
    
    if (empty($this->rows)){
      if ($this->empty_html !== null){
        echo '<td colspan="'.count($this->columns).'">';
        echo $this->empty_html;
        echo '</td>';
      }
    }
    else{
      foreach($this->rows as $row){
        $row->echoBody();
      }
    }
    
    echo <<<HTML
  </tbody>
HTML;
    
    if ($this->footer !== null){
      echo '<tfoot><tr><td colspan="'.count($this->columns).'">';
      $this->footer->echoBody();
      echo '</td></tr></tfoot>';
    }
    
    echo <<<HTML
</table>
HTML;
  }
}

?>
