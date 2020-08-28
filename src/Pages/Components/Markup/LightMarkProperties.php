<?php
declare(strict_types = 1);

namespace Pages\Components\Markup;

final class LightMarkProperties{
  private ?string $checkbox_name = null;
  private array $allowed_link_schemes = [];
  private array $allowed_image_schemes = [];
  
  public function setCheckBoxName(string $name): self{
    $this->checkbox_name = $name;
    return $this;
  }
  
  public function getCheckBoxName(): ?string{
    return $this->checkbox_name;
  }
  
  public function setAllowedLinkSchemes(array $schemes): self{
    $this->allowed_link_schemes = $schemes;
    return $this;
  }
  
  public function isAllowedLinkScheme(string $scheme): bool{
    return in_array($scheme, $this->allowed_link_schemes, true);
  }
  
  public function setAllowedImageSchemes(array $schemes): self{
    $this->allowed_image_schemes = $schemes;
    return $this;
  }
  
  public function isAllowedImageScheme(string $scheme): bool{
    return in_array($scheme, $this->allowed_image_schemes, true);
  }
}

?>
