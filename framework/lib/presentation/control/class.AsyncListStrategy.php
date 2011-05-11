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
 * $Id: class.Control.php -1   $
 */
require_once(WCMF_BASE."wcmf/lib/presentation/control/class.Control.php");
require_once(WCMF_BASE."wcmf/lib/presentation/control/class.IListStrategy.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.ObjectId.php");

/**
 * @class AsyncListStrategy
 * @ingroup Presentation
 * @brief AsyncListStrategy implements a list of entities that is retrieved
 * asynchronously from the server, where the keys are the object ids and the
 * values are the display values.
 * The following list definition(s) must be used in the input_type configuraton:
 * @code
 * async:type // list with all entities of the given type
 *
 * async:type|type.name LIKE 'A%' // list with all entities of the given type that
 *                                   match the given query (@see StringQuery)
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class AsyncListStrategy implements IListStrategy
{
  /**
   * @see IListStrategy::getListMap
   */
  public function getListMap($configuration, $value=null, $nodeOid=null, $language=null)
  {
    // load the translated value only
    $parts = preg_split('/\|/', $configuration);
    $entityType = array_shift($parts);
    // check for (remote) oid
    if (ObjectId::isValid($nodeOid))
    {
      $oid = ObjectId::parse($nodeOid);
      $typeOid = new ObjectId($entityType, $value, $oid->getPrefix());

      if (ObjectId::isValid($typeOid->_toString())) {
        $map[$value] = self::resolveByOid($typeOid, $language);
      }
    }
    else
    {
      // since this may be a multivalue field, the ids may be separated by commas
      $ids = split(',', $value);
      foreach ($ids as $id)
      {
        // oid may be pre-set
        $oid = new ObjectId($entityType, $id);
        $resolvedValue = self::resolveByOid($oid, $language);
        if ($resolvedValue) {
          $map[$id] = $resolvedValue;
        }
      }
    }
    // fallback if the value can not be interpreted as an oid or the object does not exist
    if (sizeof($map) == 0) {
      $map = array($value => $value);
    }
    return $map;
  }
  /**
   * Resolves the display value of the given oid.
   * @param oid The oid of the requested object.
   * @param language The lanugage if Control should be localization aware. Optional,
   *                 default is Localization::getDefaultLanguage()
   * @return String The display value of oid, or null if oid is invalid.
   */
  private static function resolveByOid(ObjectId $oid, $language=null)
  {
    $result = null;
    if (ObjectId::isValid($oid))
    {
      $persistenceFacade = PersistenceFacade::getInstance();
      $localization = Localization::getInstance();
      try {
        $obj = $persistenceFacade->load($oid, BUILDDEPTH_SINGLE);
        if ($obj != null)
        {
          // localize object if requested
          if ($language != null) {
            $localization->loadTranslation($obj, $language);
          }
          $result = $obj->getDisplayValue();
        }
      } catch (Exception $ex) {
        //do  nothing, $result stays null
      }
    }
    return $result;
  }
}

// register this list strategy
Control::registerListStrategy('async', 'AsyncListStrategy');
?>
