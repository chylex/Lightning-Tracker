<?php
declare(strict_types = 1);

namespace Pages\Components\Markup;

use Pages\IViewable;

/**
 * LightMark is a Markdown-like markup language that supports a limited subset of Markdown, adds some new features and removes some of Markdown's idiosyncrasies.
 * The following examples outline the features and transformation rules.
 *
 * # Paragraphs
 *
 * ```
 * first line\nsecond line   --> <p>first line<br>second line</p>
 * first line\n\nsecond line --> <p>first line</p><p>second line</p>
 * ```
 *
 * # Block
 *
 * Using > at the beginning of a line starts a quote or continues a quote from the previous line.
 *
 * Using - or * at the beginning of a line starts a list or continues a list from the previous line.
 * Indenting/unindenting the dash or asterisk increases/decreases the indentation level of the list.
 *
 * Surrounding a block with three ` on individual lines styles the contents as code.
 *
 * # Inline
 *
 * ```
 * *italic*            --> <em>italic</em>
 * **bold**            --> <strong>bold</strong>
 * ***bold + italic*** --> <strong><em>bold + italic</em></strong>
 * __underline__       --> <ins>underline</ins>
 * ~~strikethrough~~   --> <del>strikethrough</del>
 *
 * `code` --> <code>code</code>
 *
 * \[text](url) --> (link)
 * \![alt](url) --> (image with alt text)
 *
 * ```
 *
 * # Full Line
 *
 * ```
 * # Heading   --> <h4>Heading</h4>
 * ## Heading  --> <h5>Heading</h5>
 * ### Heading --> <h6>Heading</h6>
 *
 * [] Text  --> (unchecked checkbox)
 * [ ] Text --> (unchecked checkbox)
 * [x] Text --> (checked checkbox)
 * [X] Text --> (checked checkbox)
 * ```
 */
final class LightMarkComponent implements IViewable{
  public static function echoHead(): void{
    if (DEBUG){
      echo '<link rel="stylesheet" type="text/css" href="~resources/css/lightmark.css?v='.TRACKER_RESOURCE_VERSION.'">';
    }
  }
  
  private string $text;
  private ?string $checkbox_name = null;
  
  public function __construct(string $text){
    $this->text = $text;
  }
  
  public function setCheckboxNameForEditing(string $checkbox_name): self{
    $this->checkbox_name = $checkbox_name;
    return $this;
  }
  
  public function getRawText(): string{
    return $this->text;
  }
  
  public function parse(): LightMarkParseResult{
    $parser = new LightMarkParser($this->checkbox_name);
    $iter = new UnicodeIterator();
    $lines = mb_split("\n", $this->text);
    
    foreach($lines as $line){
      $iter->prepare($line);
      $parser->parseLine($iter);
    }
    
    return $parser->closeParser();
  }
  
  public function echoBody(): void{
    $this->parse()->echoBody();
  }
}

?>
