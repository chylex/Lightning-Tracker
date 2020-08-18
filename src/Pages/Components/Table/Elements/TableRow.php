<?php
declare(strict_types = 1);

namespace Pages\Components\Table\Elements;

use Pages\IViewable;

final class TableRow implements IViewable{
  /**
   * @var TableColumn[]
   */
  private array $columns_ref;
  private array $values;
  
  private ?string $link = null;
  
  public function __construct(array &$columns_ref, array $values){
    $this->columns_ref = &$columns_ref;
    $this->values = $values;
  }
  
  public function link(string $link): self{
    $this->link = $link;
    return $this;
  }
  
  /** @noinspection HtmlMissingClosingTag */
  public function echoBody(): void{
    $link = $this->link;
    
    echo $link === null ? '<tr>' : '<tr class="link">';
    
    for($index = 0, $count = count($this->columns_ref); $index < $count; $index++){
      $column = $this->columns_ref[$index];
      $value = $this->values[$index];
      
      $column->echoCellStart();
      
      if ($link !== null){
        echo '<a href="'.BASE_URL_ENC.'/'.ltrim($link, '/').'">';
      }
      
      if ($value instanceof IViewable){
        $value->echoBody();
      }
      else{
        echo $value;
      }
      
      if ($link !== null){
        echo '</a>';
      }
      
      $column->echoCellEnd();
    }
    
    echo '</tr>';
  }
}

?>
