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
  const DUMMY_ID_PATTERN = 'wcmf[A-Za-z0-9]{32}';

  private ?string $prefix;
  private string $fqType;
  private array $id;
  private ?string $strVal = null;

  private static string $idPattern = '/^[0-9]*$|^'.self::DUMMY_ID_PATTERN.'$/';
  private static string $delimiterPattern = '/'.self::DELIMITER.'/';
  private static array $numPkKeys = [];

  private static ?ObjectId $nullOID = null;

  /**
   * Constructor.
   * @param string $type The type name of the object (either fully qualified or simple, if not ambiguous)
   * @param int|string|array $id Either a single value or an array of values (for compound primary keys) identifying
   * the object between others of the same type. If not given, a dummy id will be
   * assigned (optional, default: empty array)
   * @param string $prefix A prefix for identifying a set of objects belonging to one storage in a
   * distributed environment (optional, default: _null_)
   * @note If id is an array, the order of the values must match the order of the primary key names given
   * by PersistenceMapper::getPkNames().
   */
  public function __construct(string $type, $id=[], $prefix=null) {
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
   * @return self
   */
  public static function NULL_OID(): self {
    if (self::$nullOID == null) {
      self::$nullOID = new ObjectId('NULL');
    }
    return self::$nullOID;
  }

  /**
   * Get the prefix
   * @return ?string
   */
  public function getPrefix(): ?string {
    return $this->prefix;
  }

  /**
   * Get the type (including namespace)
   * @return string
   */
  public function getType(): string {
    return $this->fqType;
  }

  /**
   * Get the id
   * @return array<int|string>
   */
  public function getId(): array {
    return $this->id;
  }

  /**
   * Get the first id. This is especially useful, when you know that this id only consists of one id.
   * @return int|string
   */
  public function getFirstId() {
    return $this->id[0];
  }

  /**
   * Check if a serialized ObjectId has a valid syntax, the type is known and
   * if the number of primary keys match the type.
   * @param string $oid The serialized ObjectId.
   * @return bool
   */
  public static function isValid(string $oid): bool {
    if (self::parse($oid) == null) {
      return false;
    }
    return true;
  }

  /**
   * Parse a serialized object id string into an ObjectId instance.
   * @param string $oid The string
   * @return ObjectId or null, if the id cannot be parsed
   */
  public static function parse(string $oid): ?ObjectId {
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

    return new ObjectId($type, $ids, $prefix);
  }

  /**
   * Parse the given object id string into it's parts
   * @param string $oid The string
   * @return array{'type': string, 'id': array<int|string>, 'prefix': string} or null
   */
  private static function parseOidString(string $oid): ?array {
    if (strlen($oid) == 0) {
      return null;
    }

    $oidParts = preg_split(self::$delimiterPattern, $oid);
    if (sizeof($oidParts) < 2) {
      return null;
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
   * @return string
   */
  public function __toString(): string {
    if ($this->strVal == null) {
      $oidStr = $this->fqType.self::DELIMITER.join(self::DELIMITER, $this->id);
      if ($this->prefix != null && strlen(trim($this->prefix)) > 0) {
        $oidStr = $this->prefix.self::DELIMITER.$oidStr;
      }
      $this->strVal = $oidStr;
    }
    return $this->strVal;
  }

  /**
   * Get a dummy id ("wcmf" + unique 32 character string).
   * @return string
   */
  public static function getDummyId(): string {
    return 'wcmf'.str_replace('-', '', StringUtil::guidv4());
  }

  /**
   * Check if a given id is a dummy id.
   * @param int|string $id The id to check
   * @return bool
   */
  public static function isDummyId($id): bool {
    return is_string($id) && (strlen($id) == 36 && strpos($id, 'wcmf') === 0);
  }

  /**
   * Check if this object id contains a dummy id.
   * @return bool
   */
  public function containsDummyIds(): bool {
    foreach ($this->getId() as $id) {
      if (self::isDummyId($id)) {
        return true;
      }
    }
    return false;
  }

  /**
   * Get the number of primary keys a type has.
   * @param string $type The type
   * @return int (1 if the type is unknown)
   */
  private static function getNumberOfPKs(string $type): int {
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

  public function serialize() {
    return $this->__toString();
  }

  public function unserialize($data): void {
    $oidParts = self::parseOidString($data);
    if ($oidParts != null) {
      $this->prefix = $oidParts['prefix'];
      $this->fqType = $oidParts['type'];
      $this->id = $oidParts['id'];
    }
    $this->strVal = $this->__toString();
  }

  public function jsonSerialize(): string {
    return $this->__toString();
  }
}
?>
