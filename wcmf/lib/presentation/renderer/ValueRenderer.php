<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2009 wemove digital solutions GmbH
 *
 * Licensed under the terms of any of the following licenses
 * at your choice:
 *
 * - GNU Lesser General Public License (LGPL)
 *   http://www.gnu.org/licenses/lgpl.html
 * - Eclipse Public License (EPL)
 *   http://www.eclipse.org/org/documents/epl-v10.php
 *
 * See the license.txt file distributed with this work for
 * additional information.
 *
 * $Id$
 */
namespace wcmf\lib\presentation\renderer;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\presentation\renderer\DisplayType;

/**
 * ValueRenderer is used to render values in html. It uses DisplayType
 * instances to render values of different display types.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class ValueRenderer {

  /**
   * Associative array mapping display types to DisplayType instances
   */
  private static $_displayTypes = array();

  /**
   * Set the concrete DisplayType instances.
   * @param displayTypes Associative array with the
   *   display types as keys and the DisplayType instances as values
   */
  public static function setDisplayTypes($displayTypes) {
    foreach ($displayTypes as $type => $instance) {
      if (!$instance instanceof DisplayType) {
        throw new ConfigurationException($instance." does not implement DisplayType");
      }
    }
    self::$_displayTypes = $displayTypes;
  }

  /**
   * Get an instance of the DisplayType class that matches the given displayType.
   * @param displayType The value type to get the DisplayType instance for
   * @return A DisplayType instance
   */
  public static function getDisplayType($displayType) {
    // get best matching display type definition
    $bestMatch = '';
    foreach (array_keys(self::$_displayTypes) as $rendererDef) {
      if (strpos($displayType, $rendererDef) === 0 && strlen($rendererDef) > strlen($bestMatch)) {
        $bestMatch = $rendererDef;
      }
    }
    // get the renderer
    if (strlen($bestMatch) > 0) {
      $renderer = self::$_displayTypes[$bestMatch];
      return $renderer;
    }
    // no match found
    throw new ConfigurationException("No DisplayType found for display type '".$displayType."'");
  }
}
?>
