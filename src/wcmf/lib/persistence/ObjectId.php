<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\persistence;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\util\StringUtil;

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
class ObjectId implements \Serializable, \JsonSerializable {

  const DELIMITER = ':';

  private $prefix;
  private $fqType;
  private $id;
  private $strVal = null;

  private static $dummyIdPattern = 'wcmf[A-Za-z0-9]{32}';
  private static $idPattern = null;
  private static $delimiterPattern = null;
  private static $numPkKeys = [];

  private static $nullOID = null;

  /**
   * Constructor.
   * @param $type The type name of the object (either fully qualified or simple, if not ambiguous)
   * @param $id Either a single value or an array of values (for compound primary keys) identifying
   * the object between others of the same type. If not given, a dummy id will be
   * assigned (optional, default: empty array)
   * @param $prefix A prefix for identifying a set of objects belonging to one storage in a
   * distributed environment (optional, default: _null_)
   * @note If id is an array, the order of the values must match the order of the primary key names given
   * by PersistenceMapper::getPkNames().
   */
  public function __construct($type, $id=[], $prefix=null) {
    $this->prefix = $prefix;
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $this->fqType = $type != 'NULL' ? $persistenceFacade->getFullyQualifiedType($type) : 'NULL';

    // get given primary keys
    $this->id = !is_array($id) ? [$id] : $id;

    // add dummy ids for missing primary key values
    $numPKs = self::getNumberOfPKs($type);
    while (sizeof($this->id) < $numPKs) {
      $this->id[] = self::getDummyId();
    }

    // set strVal immediatly otherwise object comparison will fail in
    // cases where __toString was only called on one instance
    $this->strVal = $this->__toString();
  }

  /**
   * Get the NULL instance
   * @return String
   */
  public static function NULL_OID() {
    if (self::$nullOID == null) {
      self::$nullOID = new ObjectId('NULL');
    }
    return self::$nullOID;
  }

  /**
   * Get the prefix
   * @return String
   */
  public function getPrefix() {
    return $this->prefix;
  }

  /**
   * Get the type (including namespace)
   * @return String
   */
  public function getType() {
    return $this->fqType;
  }

  /**
   * Get the id
   * @return Array
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Get the first id. This is especially useful, when you know that this id only consists of one id.
   * @return String
   */
  public function getFirstId() {
    return $this->id[0];
  }

  /**
   * Check if a serialized ObjectId has a valid syntax, the type is known and
   * if the number of primary keys match the type.
   * @param $oid The serialized ObjectId.
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
   * @param $oid The string
   * @return ObjectId or null, if the id cannot be parsed
   */
  public static function parse($oid) {
    // fast checks first
    if ($oid instanceof ObjectId) {
      return $oid;
    }

    $oidParts = self::parseOidString($oid);
    if (!$oidParts) {
      return null;
    }

    $type = $oidParts['type'];
    $ids = $oidParts['id'];
    $prefix = $oidParts['prefix'];

    // check the type
    if (!ObjectFactory::getInstance('persistenceFacade')->isKnownType($type)) {
      return null;
    }

    // check if number of ids match the type
    $numPks = self::getNumberOfPKs($type);
    if ($numPks == null || $numPks != sizeof($ids)) {
      return null;
    }

    return new ObjectID($type, $ids, $prefix);
  }

  /**
   * Parse the given object id string into it's parts
   * @param $oid The string
   * @return Associative array with keys 'type', 'id', 'prefix'
   */
  private static function parseOidString($oid) {
    if (strlen($oid ?? '') == 0) {
      return null;
    }

    $oidParts = preg_split(self::getDelimiterPattern(), $oid);
    if (sizeof($oidParts) < 2) {
      return null;
    }

    if (self::$idPattern == null) {
      self::$idPattern = '/^[0-9]*$|^'.self::$dummyIdPattern.'$/';
    }

    // get the ids from the oid
    $ids = [];
    $nextPart = array_pop($oidParts);
    while($nextPart !== null && preg_match(self::$idPattern, $nextPart) == 1) {
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

    // get the prefix
    $prefix = join(self::DELIMITER, $oidParts);

    return [
      'type' => $type,
      'id' => $ids,
      'prefix' => $prefix
    ];
  }

  /**
   * Get a string representation of the object id.
   * @return String
   */
  public function __toString() {
    if ($this->strVal == null) {
      $oidStr = $this->fqType.self::DELIMITER.join(self::DELIMITER, $this->id);
      if (strlen(trim($this->prefix ?? '')) > 0) {
        $oidStr = $this->prefix.self::DELIMITER.$oidStr;
      }
      $this->strVal = $oidStr;
    }
    return $this->strVal;
  }

  /**
   * Get a dummy id ("wcmf" + unique 32 character string).
   * @return String
   */
  public static function getDummyId() {
    return 'wcmf'.str_replace('-', '', StringUtil::guidv4());
  }

  /**
   * Check if a given id is a dummy id.
   * @param $id The id to check
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
   * @param $type The type
   * @return Integer (1 if the type is unknown)
   */
  private static function getNumberOfPKs($type) {
    if (!isset(self::$numPkKeys[$type])) {
      $numPKs = 1;
      $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
      if ($persistenceFacade->isKnownType($type)) {
        $mapper = $persistenceFacade->getMapper($type);
        $numPKs = sizeof($mapper->getPKNames());
      }
      self::$numPkKeys[$type] = $numPKs;
    }
    return self::$numPkKeys[$type];
  }

  private static function getDelimiterPattern() {
    if (self::$delimiterPattern == null) {
      self::$delimiterPattern = '/'.self::DELIMITER.'/';
    }
    return self::$delimiterPattern;
  }

  public function serialize() {
    return $this->__serialize()['data'];
  }

  public function __serialize() {
    return ['data' => $this->__toString()];
  }

  public function unserialize($data) {
    $this->__unserialize($data);
  }

  public function __unserialize($data) {
    $oidParts = self::parseOidString($data['data']);
    $this->prefix = $oidParts['prefix'];
    $this->fqType = $oidParts['type'];
    $this->id = $oidParts['id'];
    $this->strVal = $this->__toString();
  }

  public function jsonSerialize(): mixed {
    return $this->__toString();
  }
}
?>
