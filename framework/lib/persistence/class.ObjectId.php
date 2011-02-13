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

/**
 * @class ObjectId
 * @ingroup Persistence
 * @brief The unique identifier of an object.
 *
 * @note The ObjectId must provide enough information to select the appropriate mapper for the object.
 *       This may be achived by different strategies, e.g. coding the object type into the ObjectId or
 *       having a global registry which maps ObjectIds to objects. wCMF uses the first method.
 *       Serialized ObjectIds have the following notation: prefix:type:id1:id2:... where type is the object type
 *       and id1, id2, .. are the values of the primary key columns (in case of simple keys only one).
 *       Serialization is done using the __toString method (using the ObjectId instance in a string context).
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ObjectId
{
  private $_prefix;
  private $_type;
  private $_id;

  private static $_dummyIdPattern = 'wcmf[A-Za-z0-9]{32}';
  private static $_idPattern = null;
  private static $_numPkKeys = array();


  /**
   * Constructor.
   * @param type The type of the object
   * @param id Either a single value or an array of values (for compound primary keys) identifying
   * @param prefix Either a single value or an array of values (for compound primary keys) identifying
   * the object between others of the same type. [optional, default: null]
   * If id is an array, the order of the values must match the order of the primary key names given
   * by PersistenceMapper::getPkNames().
   * If only type is given, the id will be set with initial values.
   */
  public function __construct($type, $id=null, $prefix=null)
  {
    $this->_prefix = $prefix;
    $this->_type = $type;

    // get given primary keys
    if ($id != null)
    {
      if (!is_array($id)) {
        $this->_id = array($id);
      }
      else {
        $this->_id = $id;
      }
    }
    else {
      $this->_id = array();
    }

    // add dummy ids for missing primary key values
    $numPKs = self::getNumberOfPKs($type);
    while (sizeof($this->_id) < $numPKs) {
      array_push($this->_id, self::getDummyId());
    }
  }

  /**
   * Get the prefix
   * @return String
   */
  public function getPrefix()
  {
    return $this->_prefix;
  }

  /**
   * Get the type
   * @return String
   */
  public function getType()
  {
    return $this->_type;
  }

  /**
   * Get the id
   * @return Array
   */
  public function getId()
  {
    return $this->_id;
  }

  /**
   * Get the first id. This is especially usefull, when you know that this id only consists of one id.
   * @return String
   */
  public function getFirstId()
  {
    return $this->_id[0];
  }

  /**
   * Check if a serialized ObjectId has a valid syntax, the type is known and
   * if the number of primary keys match the type.
   * @param oid The serialized ObjectId.
   * @return Boolean
   */
  public static function isValid($oid)
  {
    if (self::parse($oid) == null) {
      return false;
    }
    return true;
  }

  /**
   * Parse a serialized object id string into an ObjectId instance.
   * @param oid The string
   * @return ObjectId or null, if the id cannot be parsed
   */
  public static function parse($oid)
  {
    // fast checks first
    if (strlen($oid) == 0) {
      return null;
    }

    $oidParts = preg_split('/:/', $oid);
    if (!is_array($oidParts)) {
      return null;
    }

    if (self::$_idPattern == null) {
      self::$_idPattern = '/^[0-9]*$|^'.self::$_dummyIdPattern.'$/';
    }

    // get the ids from the oid
    $ids = array();
    $nextPart = array_pop($oidParts);
    while($nextPart !== null && preg_match(self::$_idPattern, $nextPart) == 1)
    {
      $intNextPart = (int)$nextPart;
      if ($nextPart == $intNextPart) {
        $ids[] = $intNextPart;
      }
      else {
        $ids[] = $nextPart;
      }
      $nextPart = array_pop($oidParts);
    }
    $ids = array_reverse($ids);

    // get the type
    $type = $nextPart;
    if (!PersistenceFacade::getInstance()->isKnownType($type)) {
      return null;
    }

    // check if number of ids match the type
    $numPks = self::getNumberOfPKs($type);
    if ($numPks == null || $numPks != sizeof($ids)) {
      return null;
    }

    // get the prefix
    $prefix = join(':', $oidParts);

    return new ObjectID($type, $ids, $prefix);
  }

  /**
   * Get a string representation of the object id.
   * @return String
   */
  public function __toString() {
    $oidStr = $this->_type.':'.join(':', $this->_id);
    if (strlen(trim($this->_prefix)) > 0) {
      $oidStr = $this->_prefix.':'.$oidStr;
    }
    return $oidStr;
  }

  /**
   * Get a dummy id ("wcmf" + unique 32 character string).
   * @return String
   */
  public static function getDummyId()
  {
    return 'wcmf'.md5(uniqid(ip2long(@$_SERVER['REMOTE_ADDR']) ^ (int)@$_SERVER['REMOTE_PORT'] ^ @getmypid() ^ @disk_free_space(sys_get_temp_dir()), 1));
  }

  /**
   * Check if a given id is a dummy id.
   * @param id The id to check
   * @return Boolean
   */
  public static function isDummyId($id)
  {
    return (strlen($id) == 36 && strpos($id, 'wcmf') === 0);
  }

  /**
   * Check if a given object id contains a dummy id.
   * @return Boolean
   */
  public function containsDummyIds()
  {
    foreach ($this->getId() as $id) {
      if (self::isDummyId($id)) {
        return true;
      }
    }
    return false;
  }

  /**
   * Get the number of primary keys a type has.
   * @param type The type
   * @return Integer (1 if the type is unknown)
   */
  private static function getNumberOfPKs($type)
  {
    if (!isset(self::$_numPkKeys[$type]))
    {
      $numPKs = 1;
      if (PersistenceFacade::getInstance()->isKnownType($type))
      {
        $mapper = PersistenceFacade::getInstance()->getMapper($type);
        $numPKs = sizeof($mapper->getPKNames());
      }
      self::$_numPkKeys[$type] = $numPKs;
    }
    return self::$_numPkKeys[$type];
  }
}
?>
