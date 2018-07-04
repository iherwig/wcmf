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
}
?>
