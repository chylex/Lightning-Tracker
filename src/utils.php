<?php
declare(strict_types = 1);

function protect(string $text): string{
  return htmlspecialchars($text, ENT_HTML5 | ENT_QUOTES | ENT_SUBSTITUTE);
}

function bind(PDOStatement $stmt, string $param, $value, int $type = PDO::PARAM_STR): void{
  if (strpos($stmt->queryString, ':'.$param) !== false){
    $stmt->bindValue($param, $value, $type);
  }
}

function mb_str_starts_with(string $haystack, string $needle): bool{
  return mb_substr($haystack, 0, mb_strlen($needle)) === $needle;
}

function mb_str_ends_with(string $haystack, string $needle): bool{
  return mb_substr($haystack, -mb_strlen($needle)) === $needle;
}

function get_int(array $array, string $key): ?int{
  return isset($array[$key]) && is_numeric($array[$key]) ? (int)$array[$key] : null;
}

?>
