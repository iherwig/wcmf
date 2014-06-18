<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\presentation\control;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\config\ConfigurationException;
use wcmf\lib\util\StringUtil;

/**
 * ValueListProvider provides lists of key/values to be used
 * with input controls.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ValueListProvider {

  /**
   * Array of known list strategy instances
   */
  private static $_listStrategies = null;

  /**
   * Get a list of key/value pairs defined by the given configuration.
   * @param definition The list definition as used in the input_type definition
   *                  (e.g. config:ConfigSection)
   * @param language The lanugage if the values should be localized. Optional,
   *                  default is Localization::getDefaultLanguage()
   * @return An assoziative array with keys 'items' (array containing the key/value pairs)
   *                  and 'isStatic'
   */
  public static function getList($definition, $language=null) {

    $result = array();
    $listDef = self::parseListDefinition($definition);

    // get the strategy
    $strategy = self::getListStrategy($listDef['type']);

    // build list
    $result['items'] = $strategy->getList($listDef['config'], $language);
    $result['isStatic'] = $strategy->isStatic($listDef['config']);

    return $result;
  }

  /**
   * Translate a value with use of it's assoziated input type e.g get the location string from a location id.
   * (this is only done when the input type has a list definition).
   * @param value The value to translate (maybe comma separated list for list controls)
   * @param inputType The description of the control as given in the input_type property of a value
   * @param language The language if the value should be localized. Optional,
   *                 default is Localization::getDefaultLanguage()
   * @return The translated value
   */
  public static function translateValue($value, $inputType, $language=null) {
    // get definition and list from inputType
    if (strPos($inputType, '#') && strlen($value) > 0) {
      $translated = '';
      list(, $listDef) = preg_split('/#/', $inputType, 2);
      $list = self::getList($listDef, $language);
      if (strPos($value, ',')) {
        $value = preg_split('/,/', $value);
      }
      if (is_array($value)) {
        foreach($value as $curValue) {
          $curValue = trim($curValue);
          $translated .= (isset($list[$curValue]) ? $list[$curValue] : $value).", ";
        }
        $translated = StringUtil::removeTrailingComma($translated);
      }
      else {
        $value = trim($value);
        $translated = isset($list[$value]) ? $list[$value] : $value;
      }
      return $translated;
    }
    return $value;
  }

  /**
   * Parse the given list definition
   * @param definition The list definition as used in the input_type definition
   *                  (e.g. config:ConfigSection)
   * @return Associative array with keys 'type' and 'config'
   * @throws ConfigurationException
   */
  protected static function parseListDefinition($definition) {
    if (!strPos($definition, ':')) {
      throw new ConfigurationException("No type found in list definition: ".$definition);
    }
    else {
      list($listType, $configuration) = preg_split('/:/', $definition, 2);
    }
    return array(
      'type' => $listType,
      'config' => $configuration
    );
  }

  /**
   * Get the ListStrategy instance for a given list type
   * @param listType The list type
   * @return ListStrategy instance
   * @throws ConfigurationException
   */
  protected static function getListStrategy($listType) {
    // get list strategies
    if (self::$_listStrategies == null) {
      self::$_listStrategies = ObjectFactory::getInstance('listStrategies');
    }

    $strategy = null;

    // search strategy
    if (isset(self::$_listStrategies[$listType])) {
      $strategy = self::$_listStrategies[$listType];
    }
    else {
      throw new ConfigurationException('No ListStrategy implementation registered for '.$listType);
    }
    return $strategy;
  }
}
?>
