<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
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
 */
namespace wcmf\lib\presentation\control\lists\impl;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\i18n\Message;
use wcmf\lib\presentation\control\lists\ListStrategy;

/**
 * ConfigListStrategy implements list of key value pairs that is retrieved
 * from an configuration section.
 * The following list definition(s) must be used in the input_type configuraton:
 * @code
 * config:section // where section is the name of a configuration section
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ConfigListStrategy implements ListStrategy {

  private $_lists = array();

  /**
   * @see ListStrategy::getList
   */
  public function getList($configuration, $language=null) {
    $listKey = $configuration.$language;
    if (!isset($this->_lists[$listKey])) {
      $config = ObjectFactory::getConfigurationInstance();
      $map = $config->getSection($configuration);
      $result = array();
      foreach ($map as $key => $value) {
        $result[$key] = Message::get($value, null, $language);
      }
      $this->_lists[$listKey] = $result;
    }
    return $this->_lists[$listKey];
  }

  /**
   * @see ListStrategy::isStatic
   */
  public function isStatic($configuration) {
    return true;
  }
}
?>
