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
namespace wcmf\lib\presentation\control\lists\impl;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\control\lists\ListStrategy;
use wcmf\lib\util\StringUtil;

/**
 * ConfigListStrategy implements a list of key/value pairs that is retrieved
 * from a configuration section.
 *
 * Configuration example:
 * @code
 * // configuration section EntityStage
 * {"type":"config","section":"EntityStage"}
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ConfigListStrategy implements ListStrategy {

  private $lists = [];

  /**
   * @see ListStrategy::getList
   * $options is an associative array with key 'section'
   */
  public function getList($options, $valuePattern=null, $key=null, $language=null) {
    if (!isset($options['section'])) {
      throw new ConfigurationException("No 'pattern' given in list options: "+StringUtil::getDump($options));
    }
    $section = $options['section'];
    $listKey = $section.$language;
    if (!isset($this->lists[$listKey])) {
      $config = ObjectFactory::getInstance('configuration');
      $map = $config->getSection($section);
      $result = [];
      $message = ObjectFactory::getInstance('message');
      foreach ($map as $curKey => $curValue) {
        $displayValue = $message->getText($curValue, null, $language);
        if ((!$valuePattern || preg_match($valuePattern, $displayValue)) && (!$key || $key == $curKey)) {
          $result[$curKey] = $displayValue;
        }
      }
      $this->lists[$listKey] = $result;
    }
    return $this->lists[$listKey];
  }

  /**
   * @see ListStrategy::isStatic
   */
  public function isStatic($options) {
    return true;
  }
}
?>
