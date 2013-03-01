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
namespace wcmf\lib\presentation\control\lists\impl;

use wcmf\lib\core\ObjectFactory;
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

  /**
   * @see ListStrategy::getListMap
   */
  public function getListMap($configuration, $value=null, $nodeOid=null, $language=null) {
    $config = ObjectFactory::getConfigurationInstance();
    $map = $config->getSection($configuration);
    return $map;
  }
}
?>
