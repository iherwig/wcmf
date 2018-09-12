<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2018 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
use wcmf\lib\util\DOMUtils;

/**
 * Add classes to elements in an html string
 *
 * Example:
 * @code
 * {$html|add_styles:['p' => 'contact lh-title f4 f3-m f2_3-l near-black db pb2 l','a' => 'link near-black']}
 * @endcode
 *
 * @param $string The html string
 * @param $styles Map with element names as keys and string with class names as values
 * @return String
 */
function smarty_modifier_add_styles($html, $styles) {
  return strlen($html) > 0 ? DOMUtils::processHtml($html, function(\DOMDocument $doc) use ($styles) {
    $xpath = new \DOMXpath($doc);
    foreach ($styles as $name => $classes) {
      $elements = $xpath->query("//".$name);
      foreach ($elements as $element) {
        $existingClasses = explode(" ", $element->getAttribute("class"));
        $allClasses = array_unique(array_merge($existingClasses, explode(" ", $classes)));
        $element->setAttribute("class", join(" ", $allClasses));
      }
    }
  }) : '';
}
?>