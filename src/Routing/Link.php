<?php
declare(strict_types = 1);

namespace Routing;

final class Link{
  public static function fromRoot(...$parts): string{
    return self::process([BASE_URL_ENC, ...$parts]);
  }
  
  public static function fromBase(Request $req, ...$parts): string{
    return self::process([BASE_URL_ENC, $req->getBasePath()->encoded(), ...$parts]);
  }
  
  /**
   * @param Request $req
   * @param string $key
   * @param mixed $new_value
   * @return string
   */
  public static function withGet(Request $req, string $key, $new_value): string{
    $data = $_GET;
    
    if ($new_value === null){
      unset($data[$key]);
    }
    else{
      $data[$key] = $new_value;
    }
    
    $query = http_build_query($data);
    
    if (!empty($query)){
      $query = '/?'.$query;
    }
    
    return self::process([BASE_URL_ENC, $req->getFullPath()->encoded().$query]);
  }
  
  private static function process(array $parts): string{
    return implode('/', array_filter(array_map(fn($part): string => trim(strval($part), '/'), $parts), fn($part): bool => !empty($part)));
  }
}

?>
