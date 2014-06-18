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
namespace wcmf\lib\persistence;

use wcmf\lib\core\ObjectFactory;

/**
 * ObjectId is the unique identifier of an object.
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
class ObjectId {

  private $_prefix;
  private $_fqType;
  private $_id;
  private $_strVal = null;

  private static $_dummyIdPattern = 'wcmf[A-Za-z0-9]{32}';
  private static $_idPattern = null;
  private static $_numPkKeys = array();

  private static $_nullOID = null;

  /**
   * Constructor.
   * @param type The type name of the object (either fully qualified or simple, if not ambiguous)
   * @param id Either a single value or an array of values (for compound primary keys) identifying
   * the object between others of the same type. If not given, a dummy id will be
   * assigned. [optional, default: null]
   * @param prefix A prefix for identifying a set of objects belonging to one storage in a
   * distributed enviroment.
   * @note If id is an array, the order of the values must match the order of the primary key names given
   * by PersistenceMapper::getPkNames().
   */
  public function __construct($type, $id=null, $prefix=null) {
    $this->_prefix = $prefix;
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $this->_fqType = $type != 'NULL' ? $persistenceFacade->getFullyQualifiedType($type) : 'NULL';

    // get given primary keys
    if ($id != null) {
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
      $this->_id[] = self::getDummyId();
    }

    // set strVal immediatly otherwise object comparison will fail in
    // cases where __toString was only called on one instance
    $this->_strVal = $this->__toString();
  }

  /**
   * Get the prefix
   * @return String
   */
  public static function NULL_OID() {
    if (self::$_nullOID == null) {
      self::$_nullOID = new ObjectId('NULL');
    }
    return self::$_nullOID;
  }

  /**
   * Get the prefix
   * @return String
   */
  public function getPrefix() {
    return $this->_prefix;
  }

  /**
   * Get the type (including namespace)
   * @return String
   */
  public function getType() {
    return $this->_fqType;
  }

  /**
   * Get the id
   * @return Array
   */
  public function getId() {
    return $this->_id;
  }

  /**
   * Get the first id. This is especially usefull, when you know that this id only consists of one id.
   * @return String
   */
  public function getFirstId() {
    return $this->_id[0];
  }

  /**
   * Check if a serialized ObjectId has a valid syntax, the type is known and
   * if the number of primary keys match the type.
   * @param oid The serialized ObjectId.
   * @return Boolean
   */
  public static function isValid($oid) {
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
  public static function parse($oid) {
    // fast checks first
    if ($oid instanceof ObjectId) {
      return $oid;
    }

    if (strlen($oid) == 0) {
      return null;
    }

    $oidParts = preg_split('/:/', $oid);
    if (sizeof($oidParts) < 2) {
      return null;
    }

    if (self::$_idPattern == null) {
      self::$_idPattern = '/^[0-9]*$|^'.self::$_dummyIdPattern.'$/';
    }

    // get the ids from the oid
    $ids = array();
    $nextPart = array_pop($oidParts);
    while($nextPart !== null && preg_match(self::$_idPattern, $nextPart) == 1) {
      $intNextPart = (int)$nextPart;
      if ($nextPart == (string)$intNextPart) {
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
    if (!ObjectFactory::getInstance('persistenceFacade')->isKnownType($type)) {
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
    if ($this->_strVal == null) {
      $oidStr = $this->_fqType.':'.join(':', $this->_id);
      if (strlen(trim($this->_prefix)) > 0) {
        $oidStr = $this->_prefix.':'.$oidStr;
      }
      $this->_strVal = $oidStr;
    }
    return $this->_strVal;
  }

  /**
   * Get a dummy id ("wcmf" + unique 32 character string).
   * @return String
   */
  public static function getDummyId() {
    return 'wcmf'.md5(uniqid(ip2long(@$_SERVER['REMOTE_ADDR']) ^ (int)@$_SERVER['REMOTE_PORT'] ^ @getmypid() ^ @disk_free_space(sys_get_temp_dir()), 1));
  }

  /**
   * Check if a given id is a dummy id.
   * @param id The id to check
   * @return Boolean
   */
  public static function isDummyId($id) {
    return (strlen($id) == 36 && strpos($id, 'wcmf') === 0);
  }

  /**
   * Check if this object id contains a dummy id.
   * @return Boolean
   */
  public function containsDummyIds() {
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
  private static function getNumberOfPKs($type) {
    if (!isset(self::$_numPkKeys[$type])) {
      $numPKs = 1;
      $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
      if ($persistenceFacade->isKnownType($type)) {
        $mapper = $persistenceFacade->getMapper($type);
        $numPKs = sizeof($mapper->getPKNames());
      }
      self::$_numPkKeys[$type] = $numPKs;
    }
    return self::$_numPkKeys[$type];
  }
}
?>
