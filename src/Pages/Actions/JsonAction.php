<?php
declare(strict_types = 1);

namespace Pages\Actions;

use JsonException;
use Logging\Log;
use Pages\IAction;
use Routing\RouterException;

class JsonAction implements IAction{
  private array $data;
  
  public function __construct(array $data){
    $this->data = $data;
  }
  
  public function execute(): void{
    header('Content-Type: application/json');
    
    try{
      echo json_encode($this->data, JSON_NUMERIC_CHECK | JSON_THROW_ON_ERROR);
    }catch(JsonException $e){
      Log::critical($e);
      http_response_code(RouterException::STATUS_SERVER_ERROR);
    }
  }
}

?>
