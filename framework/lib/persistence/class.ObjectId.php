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
 *       Serialized ObjectIds have the following notation: type:id1:id2:... where type is the object type
 *       and id1, id2, .. are the values of the primary key columns (in case of simple keys only one).
 *       Serialization is done using the __toString method (using the ObjectId instance in a string context).
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ObjectId
{
  private $_type;
  private $_id;

  /**
   * Constructor.
   * @param type The type of the object
   * @param id Either a single value or an array of values (for compound primary keys) identifying
   * the object between others of the same type. [optional, default: null]
   * If id is an array, the order of the values must match the order of the primary key names given
   * by PersistenceMapper::getPkNames().
   * If only type is given, the id will be set with initial values.
   */
  public function __construct($type, $id=null)
  {
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
    $numPKs = ObjectId::getNumberOfPKs($type);
    while (sizeof($this->_id) < $numPKs) {
      array_push($this->_id, ObjectId::getDummyId());
    }
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
    // we expect at least one separator to separate the type from
    // the primary key column values
    if (strpos($oid, ':') !== false)
    {
      $oidParts = split(':', $oid);
      $type = $oidParts[0];

      if (PersistenceFacade::isKnownType($type))
      {
        // get number of expected primary keys
        $numPks = ObjectId::getNumberOfPKs($type);
        return sizeof(split(':', $oid)) == $numPks+1;
      }
    }
    return false;
  }

  /**
   * Parse a serialized object id string into an ObjectId instance.
   * @param oid The string
   * @return ObjectId
   */
  public static function parse($oid)
  {
    // do simple test first
    if (ObjectId::isValid($oid))
    {
      $oidParts = split(':', $oid);
      $type = $oidParts[0];
      $ids = array();
      for ($i=1; $i<sizeof($oidParts); $i++) {
        $id = $oidParts[$i];
        if (!ObjectId::isDummyId($id)) {
          $id = intval($id);
        }
        array_push($ids, $id);
      }
      return new ObjectId($type, $ids);

    }
    else {
      throw new PersistenceException('Illegal ObjectId found: '.$oid);
    }
  }

  /**
   * Get a string representation of the object id.
   * @return String
   */
  public function __toString() {
    return $this->_type.':'.join(':', $this->_id);
  }

  /**
   * Get a dummy id ("wcmf" + unique 32 character string).
   * @return String
   */
  private static function getDummyId()
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
   * Get the number of primary keys a type has.
   * @param type The type
   * @return Integer (1 if the type is unknown)
   */
  private static function getNumberOfPKs($type)
  {
    $numPKs = 1;
    if (PersistenceFacade::isKnownType($type))
    {
      $mapper = PersistenceFacade::getInstance()->getMapper($type);
      $numPKs = sizeof($mapper->getPKNames());
    }
    return $numPKs;
  }
}
?>
