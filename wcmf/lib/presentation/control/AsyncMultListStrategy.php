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

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\presentation\control\ListStrategy;

/**
 * AsyncMultListStrategy implements a list of entities that is retrieved
 * asynchronously from the server, where the keys are the object ids and the
 * values are the display values.
 * The following list definition(s) must be used in the input_type configuraton:
 * @code
 * async:type1|type2|... // list with all entities of the given types
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class AsyncMultListStrategy implements ListStrategy {

  /**
   * @see ListStrategy::getListMap
   */
  public function getListMap($configuration, $value=null, $nodeOid=null, $language=null) {
    // load the translated value only
    $parts = preg_split('/\|/', $configuration);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    foreach($parts as $key=>$entityType) {
      // since this may be a multivalue field, the ids may be separated by commas
      $ids = preg_split('/,/', $value);
      foreach ($ids as $id) {
        $oid = new ObjectId($entityType, $id);
        if (ObjectId::isValidOID($oid->__toString())) {
          $localization = ObjectFactory::getInstance('localization');
          $obj = $persistenceFacade->load($oid, BuildDepth::SINGLE);
          if ($obj != null) {
            // localize object if requested
            if ($language != null) {
              $localization->loadTranslation($obj, $language);
            }
            $map[$id] = $obj->getDisplayValue();
          }
        }
      }
    }
    // fallback if the value can not be interpreted as an oid or the object does not exist
    if (sizeof($map) == 0) {
      $map = array($value => $value);
    }
    return $map;
  }
}
?>
