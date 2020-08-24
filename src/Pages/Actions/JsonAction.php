<?php
declare(strict_types = 1);

namespace Pages\Actions;

use Pages\IAction;

class JsonAction implements IAction{
  private array $data;
  
  public function __construct(array $data){
    $this->data = $data;
  }
  
  public function execute(): void{
    header('Content-Type: application/json');
    echo json_encode($this->data, JSON_NUMERIC_CHECK);
  }
}

?>
