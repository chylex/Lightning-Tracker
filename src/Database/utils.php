<?php
declare(strict_types = 1);

namespace Database;

use PDO;
use PDOStatement;

function protect(string $text): string{
  return htmlspecialchars($text, ENT_HTML5 | ENT_QUOTES | ENT_SUBSTITUTE);
}

function bind(PDOStatement $stmt, string $param, $value, int $type = PDO::PARAM_STR){
  if (strpos($stmt->queryString, ':'.$param) !== false){
    $stmt->bindValue($param, $value, $type);
  }
}

?>
