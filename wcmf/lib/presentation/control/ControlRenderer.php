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
namespace wcmf\lib\presentation\control;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\i18n\Localization;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\presentation\control\Control;
use wcmf\lib\util\StringUtil;

/**
 * ControlRenderer is used to render controls in html. It uses Control
 * instances to render controls of different input types.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ControlRenderer {

  /**
   * Associative array mapping control types to control instances
   */
  private static $_controls = array();

  /**
   * The delimiter for HTML input control names to be used if a control name
   * consists of different parts.
   */
  private static $_inputFieldNameDelimiter = '-';

  /**
   * Set the concrete Control instances.
   * @param controls Associative array with the
   *   control types as keys and the control instances as values
   */
  public static function setControls($controls) {
    foreach ($controls as $type => $instance) {
      if (!$instance instanceof Control) {
        throw new ConfigurationException($instance." does not implement Control");
      }
    }
    self::$_controls = $controls;
  }

  /**
   * Set the delimiter for HTML input control names to be used if a control name
   * consists of different parts.
   * @param inputFieldNameDelimiter
   */
  public static function setInputFieldNaneDelimiter($inputFieldNameDelimiter) {
    self::$_inputFieldNameDelimiter = $inputFieldNameDelimiter;
  }

  /**
   * Get a HTML input control name for a given object value.
   * @param obj A reference to the PersistentObject which contains the value
   * @param name The name of the value to construct the control for
   * @param language The lanugage if the Control should be localization aware. Optional,
   *                 default null (= Localization::getDefaultLanguage())
   * @return The HTML control name string in the form value-<name>-<oid>
   */
  public static function getControlName(PersistentObject $obj, $name, $language=null) {
    if ($language == null) {
      $language = Localization::getInstance()->getDefaultLanguage();
    }
    $fieldDelimiter = self::$_inputFieldNameDelimiter;
    return 'value'.$fieldDelimiter.$language.$fieldDelimiter.$name.$fieldDelimiter.$obj->getOID();
  }

  /**
   * Get the object value definition from a HTML input control name.
   * @param name The name of input control in the format defined by ControlRenderer::getControlName
   * @return An associative array with keys 'oid', 'language', 'name' or null if the name is not valid
   */
  public static function getValueDefFromInputControlName($name) {
    if (!(strpos($name, 'value') == 0)) {
      return null;
    }
    $def = array();
    $fieldDelimiter = StringUtil::escapeForRegex(self::$_inputFieldNameDelimiter);
    $pieces = preg_split('/'.$fieldDelimiter.'/', $name);
    if (sizeof($pieces) != 3) {
      return null;
    }
    $forget = array_shift($pieces);
    $def['language'] = array_shift($pieces);
    $def['name'] = array_shift($pieces);
    $def['oid'] = array_shift($pieces);

    return $def;
  }

  /**
   * Get an instance of the control class that matches the given inputType.
   * @param inputType The input type to get the control for (see Control::render())
   * @return A Control instance
   */
  public static function getControl($inputType) {
    // get best matching control definition
    $bestMatch = '';
    foreach (array_keys(self::$_controls) as $controlDef) {
      if (strpos($inputType, $controlDef) === 0 && strlen($controlDef) > strlen($bestMatch)) {
        $bestMatch = $controlDef;
      }
    }
    // get the control
    if (strlen($bestMatch) > 0) {
      $control = self::$_controls[$bestMatch];
      return $control;
    }
    // no match found
    throw new ConfigurationException("No control found for input type '".$inputType."'");
  }
}
?>
