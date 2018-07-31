<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\util;

/**
 * DomUtils
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DOMUtils {
  /**
   * Process the given html fragment using the given function
   * @param $content Html string
   * @param $processor Function that accepts a DOMDocument as only parameter
   * @return String
   */
  public static function processHtml($content, callable $processor) {
    $doc = new \DOMDocument();
    $doc->loadHTML('<html>'. $content.'</html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $processor($doc);
    return str_replace(['<html>', '</html>'], '', $doc->saveHTML());
  }

  /**
   * Get the child nodes of a given type
   * @param \DOMElement $element
   * @param $type
   * @return \DOMNodeList[]
   */
  public static function getChildNodesOfType(\DOMElement $element, $type) {
    $result = [];
    foreach ($element->childNodes as $child) {
      if ($child->nodeName == $type) {
        $result[] = $child;
      }
    }
    return $result;
  }

  /**
   * Get the inner html string of an element
   * @param \DOMElement $element
   * @return String
   */
  public static function getInnerHtml(\DOMElement $element) {
    $innerHTML= '';
    $children = $element->childNodes;
    foreach ($children as $child) {
      $innerHTML .= $child->ownerDocument->saveXML( $child );
    }
    return $innerHTML;
  }

  /**
   * Set the inner html string of an element
   * @param \DOMElement $element
   * @param $html
   */
  public static function setInnerHtml(\DOMElement $element, $html) {
    $fragment = $element->ownerDocument->createDocumentFragment();
    $fragment->appendXML($html);
    $element->appendChild($fragment);
  }


  /**
   * Remove double linebreaks and empty paragraphs
   * @param $content
   * @return String
   */
  public static function removeEmptyLines($html) {
    // merge multiple linebreaks to one
    $html = preg_replace("/(<br>\s*)+/", "<br>", $html);
    // remove linebreaks at the beginning of a paragraph
    $html = preg_replace("/<p>(\s|<br>)*/", "<p>", $html);
    // remove linebreaks at the end of a paragraph
    $html = preg_replace("/(\s|<br>)*<\/p>/", "</p>", $html);
    // remove empty paragraphs
    $html = preg_replace("/<p><\/p>/", "", $html);
    return $html;
  }
}
?>
