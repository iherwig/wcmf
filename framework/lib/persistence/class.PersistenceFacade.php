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
require_once(BASE."wcmf/lib/util/class.Log.php");
require_once(BASE."wcmf/lib/util/class.InifileParser.php");
require_once(BASE."wcmf/lib/output/class.OutputStrategy.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceMapper.php");
require_once(BASE."wcmf/lib/persistence/class.ObjectQuery.php");
require_once(BASE."wcmf/lib/persistence/class.StringQuery.php");
require_once(BASE."wcmf/lib/persistence/class.PagingInfo.php");
require_once(BASE."wcmf/lib/persistence/class.ChangeListener.php");

/**
 * Some constants describing the build process
 */
define("BUILDDEPTH_INFINITE", -1); // build complete tree from given root on
define("BUILDDEPTH_SINGLE",   -2); // build only given object
define("BUILDDEPTH_REQUIRED", -4); // build tree from given root on respecting the required property defined in element relations
define("BUILDDEPTH_MAX", 10);      // maximum possible creation depth in one call

/**
 * @class PersistenceFacade
 * @ingroup Persistence
 * @brief PersistenceFacade delegates persistence operations to the type-specific PersistenceMappers.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PersistenceFacade implements ChangeListener
{
  private static $_instance = null;
  private $_mapperObjects = array();
  private $_createdOIDs = array();
  private $_dbConnections = array();
  private $_cache = array();
  private $_logging = false;
  private $_logStrategy = null;
  private $_isReadOnly = false;
  private $_isCaching = false;
  private $_inTransaction = false;

  private function __construct() {}

  /**
   * Returns an instance of the class.
   * @return A reference to the only instance of the Singleton object
   */
  public static function getInstance()
  {
    if (!is_object(self::$_instance)) {
      self::$_instance = new PersistenceFacade();
    }
    return self::$_instance;
  }
  /**
   * Get a list of types defined in the application.
   * @return The list of types
   */
  function getKnownTypes()
  {
    $parser = &InifileParser::getInstance();
    return array_keys($parser->getSection('typemapping'));
  }
  /**
   * Check if a type is defined in the application.
   * @param type The type to check
   * @return True/False wether the type is defined or not
   */
  static function isKnownType($type)
  {
    // find mapper in configfile
    $parser = InifileParser::getInstance();
    if (($mapperClass = $parser->getValue($type, 'typemapping')) === false)
    {
      if (($mapperClass = $parser->getValue('*', 'typemapping')) === false) {
        return false;
      }
    }
    return true;
  }
  /**
   * Load an object from the database.
   * @param oid The object id of the object to construct
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build (except BUILDDEPTH_REQUIRED)
   * @param buildAttribs An assoziative array listing the attributes to load (default: empty array, loads all attributes)
   *        (keys: the types, values: an array of attributes of the type to load)
   *        Use this to load only a subset of attributes
   * @param buildTypes An array listing the (sub-)types to include (default: empty array, includes all types)
   * @return A reference to the object, null if oid does not exist or a given condition prevents loading.
   */
  function load(ObjectId $oid, $buildDepth, array $buildAttribs=array(), array $buildTypes=array())
  {
    if ($buildDepth < 0 && !in_array($buildDepth, array(BUILDDEPTH_INFINITE, BUILDDEPTH_SINGLE))) {
      throw new IllegalArgumentException("Build depth not supported: $buildDepth", __FILE__, __LINE__);
    }
    $obj = null;

    // lookup the object in the cache
    if ($this->_isCaching)
    {
      $cacheKey = $this->getCacheKey($oid, $buildDepth, $buildAttribs, $buildTypes);
      if (array_key_exists($cacheKey, $this->_cache)) {
        $obj = &$this->_cache[$cacheKey];
      }
    }

    // if not cached, load
    if ($obj == null)
    {
      $mapper = $this->getMapper($oid->getType());
      if ($mapper != null) {
        $obj = $mapper->load($oid, $buildDepth, $buildAttribs, $buildTypes);
      }
      if ($obj != null)
      {
        // prepare the object (readonly/locked)
        if ($this->_isReadOnly) {
          $obj->setImmutable();
        }
        // cache the object
        if ($this->_isCaching)
        {
          $cacheKey = $this->getCacheKey($oid, $buildDepth, $buildAttribs, $buildTypes);
          $this->_cache[$cacheKey] = &$obj;
        }
      }
    }
    return $obj;
  }
  /**
   * Construct the template of an Object of a given type.
   * @param type The type of object to build
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   * @param buildAttribs An assoziative array listing the attributes to create (default: empty array, creates all attributes)
   *        (keys: the types, values: an array of attributes of the type to create)
   *        Use this to create only a subset of attributes
   * @return A reference to the object.
   */
  function create($type, $buildDepth, array $buildAttribs=array())
  {
    if ($buildDepth < 0 && !in_array($buildDepth, array(BUILDDEPTH_INFINITE, BUILDDEPTH_SINGLE, BUILDDEPTH_REQUIRED))) {
      throw new IllegalArgumentException("Build depth not supported: $buildDepth");
    }
    $obj = null;
    $mapper = $this->getMapper($type);
    if ($mapper != null)
    {
      $obj = &$mapper->create($type, $buildDepth, $buildAttribs);

      // register as change listener to track the created oid, after save
      $obj->addChangeListener($this);
    }

    return $obj;
  }
  /**
   * Save an object to the database (inserted if it is new).
   * @param object A reference to the object to save
   * @return True/False depending on success of operation
   */
  function save(PersistentObject $object)
  {
    if ($this->_isReadOnly) {
      return true;
    }
    $result = $object->save();
    return $result;
  }
  /**
   * Delete an object from the database (together with all of its children).
   * @param oid The object id of the object to delete
   * @param recursive True/False whether to physically delete it's children too [default: true]
   * @return True/False depending on success of operation
   */
  function delete(ObjectId $oid, $recursive=true)
  {
    if ($this->_isReadOnly) {
      return true;
    }
    $result = false;
    $mapper = &$this->getMapper($oid->getType());
    if ($mapper != null) {
      $result = $mapper->delete($oid, $recursive);
    }
    return $result;
  }
  /**
   * Get the object ids of newly created objects of a given type.
   * @param type The type of the object
   * @return An array containing the objects ids
   */
  function getCreatedOIDs($type)
  {
    if (!array_key_exists($type, $this->_createdOIDs)) {
      return $this->_createdOIDs[$type];
    }
    return array();
  }
  /**
   * Get the object id of the last created object of a given type.
   * @param type The type of the object
   * @return The object id or null
   */
  function getLastCreatedOID($type)
  {
    if (array_key_exists($type, $this->_createdOIDs) && sizeof($this->_createdOIDs[$type]) > 0) {
      return $this->_createdOIDs[$type][sizeof($this->_createdOIDs[$type])-1];
    }
    return null;
  }
  /**
   * Get the object ids of objects matching a given criteria. If a PagingInfo instance is passed it will be used and updated.
   * @param type The type of the object
   * @param criteria An assoziative array holding name value pairs of attributes for selecting objects or a single string
   *        representing a (mapper specific) query condition (maybe null). [default: null]
   * @param orderby An array holding names of attributes to order by (maybe null). [default: null]
   * @param pagingInfo A reference PagingInfo instance. [default: null]
   * @return An array containing the objects ids
   */
  function getOIDs($type, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null)
  {
    $result = array();
    $mapper = $this->getMapper($type);
    if ($mapper != null) {
      $result = $mapper->getOIDs($type, $criteria, $orderby, $pagingInfo);
    }
    return $result;
  }
  /**
   * Get the first object id of objects matching a given condition. If a PagingInfo instance is passed it will be used and updated.
   * @param type The type of the object
   * @param criteria An assoziative array holding name value pairs of attributes for selecting objects or a single string
   *        representing a (mapper specific) query condition (maybe null). [default: null]
   * @param orderby An array holding names of attributes to ORDER by (maybe null). [default: null]
   * @param pagingInfo A reference PagingInfo instance. [default: null]
   * @return An object id or null
   */
  function getFirstOID($type, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null)
  {
    $oids = PersistenceFacade::getOIDs($type, $criteria, $orderby, $pagingInfo);
    if (sizeof($oids) > 0) {
      return $oids[0];
    }
    else {
      return null;
    }
  }
  /**
   * Load the objects matching a given condition. If a PagingInfo instance is passed it will be used and updated.
   * @param type The type of the object
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build (except BUILDDEPTH_REQUIRED)
   * @param criteria An assoziative array holding name value pairs of attributes for selecting objects or a single string
   *        representing a (mapper specific) query condition (maybe null). [default: null]
   * @param orderby An array holding names of attributes to ORDER by (maybe null). [default: null]
   * @param pagingInfo A reference PagingInfo instance (maybe null). [default: null]
   * @param buildAttribs An assoziative array listing the attributes to load (default: null loads all attributes)
   *        (keys: the types, values: an array of attributes of the type to load)
   *        Use this to load only a subset of attributes
   * @param buildTypes An array listing the (sub-)types to include
   * @return An array containing the objects
   */
  function loadObjects($type, $buildDepth, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null,
    array $buildAttribs=null, array $buildTypes=null)
  {
    $result = array();
    $mapper = $this->getMapper($type);
    if ($mapper != null) {
      $result = $mapper->loadObjects($type, $buildDepth, $criteria, $orderby, $pagingInfo, $buildAttribs, $buildTypes);
    }
    return $result;
  }
  /**
   * Load the first object matching a given condition. If a PagingInfo instance is passed it will be used and updated.
   * @param type The type of the object
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build (except BUILDDEPTH_REQUIRED)
   * @param criteria An assoziative array holding name value pairs of attributes for selecting objects or a single string
   *        representing a (mapper specific) query condition (maybe null). [default: null]
   * @param orderby An array holding names of attributes to ORDER by (maybe null). [default: null]
   * @param pagingInfo A reference PagingInfo instance. [default: null]
   * @param buildAttribs An assoziative array listing the attributes to load (default: null loads all attributes)
   *        (keys: the types, values: an array of attributes of the type to load)
   *        Use this to load only a subset of attributes
   * @param buildTypes An array listing the (sub-)types to include
   * @return A reference to the object or null
   */
  function loadFirstObject($type, $buildDepth, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null,
    array $buildAttribs=null, array $buildTypes=null)
  {
    $objects = PersistenceFacade::loadObjects($type, $buildDepth, $criteria, $orderby, $pagingInfo, $buildAttribs, $buildTypes);
    if (sizeof($objects) > 0) {
      return $objects[0];
    }
    else {
      return null;
    }
  }
  /**
   * Create an object query.
   * @param type The object type to search for
   * @return An ObjectQuery instance
   */
  function createObjectQuery($type)
  {
    return new ObjectQuery($type);
  }
  /**
   * Create a string query.
   * @return An StringQuery instance
   */
  function createStringQuery()
  {
    return new StringQuery();
  }
  /**
   * Start a transaction. Used for PersistenceMapper classes that need to explicitely start and commit transactions.
   * If this method is called, the startTransaction() method of every used PersistenceMapper will be called - until
   * commitTransaction() is called.
   * @note There is only ONE transaction active at a time. Repeated calls of this method will leave the initial
   * transaction active until commitTransaction() ore rollbackTransaction() is called.
   */
  function startTransaction()
  {
    if (!$this->_inTransaction)
    {
      // log action
      if ($this->isLogging()) {
        Log::debug("Start Transaction", __CLASS__);
      }
      // end transaction for every mapper
      $mapperEntries = array_keys($this->_mapperObjects);
      for ($i=0; $i<sizeof($mapperEntries); $i++) {
        $this->_mapperObjects[$mapperEntries[$i]]->startTransaction();
      }
      $this->_inTransaction = true;
    }
  }
  /**
   * Commit the started transaction. Used for PersistenceMapper classes that need to explicitely start and commit transactions.
   * If this method is called, the commitTransaction() method of every used PersistenceMapper will be called.
   * @note There is only ONE transaction active at a time. Repeated calls of this method will do nothing until
   * a new transaction was started by calling startTransaction().
   */
  function commitTransaction()
  {
    if ($this->_inTransaction)
    {
      // log action
      if ($this->isLogging()) {
        Log::debug("Commit Transaction", __CLASS__);
      }
      // commit transaction for every mapper
      $mapperEntries = array_keys($this->_mapperObjects);
      for ($i=0; $i<sizeof($mapperEntries); $i++) {
        $this->_mapperObjects[$mapperEntries[$i]]->commitTransaction();
      }
      $this->_inTransaction = false;
    }
  }
  /**
   * Rollback the started transaction. Used for PersistenceMapper classes that need to explicitely start and commit transactions.
   * If this method is called, the rollbackTransaction() method of every used PersistenceMapper will be called.
   * @note There is only ONE transaction active at a time. Repeated calls of this method will do nothing until
   * a new transaction was started by calling startTransaction(). Rollbacks have to be supported by the data storage.
   */
  function rollbackTransaction()
  {
    if ($this->_inTransaction)
    {
      if ($this->isLogging()) {
        Log::debug("Rollback Transaction", __CLASS__);
      }
      // rollback transaction for every mapper
      $mapperEntries = array_keys($this->_mapperObjects);
      for ($i=0; $i<sizeof($mapperEntries); $i++) {
        $this->_mapperObjects[$mapperEntries[$i]]->rollbackTransaction();
      }
      $this->_inTransaction = false;
    }
  }
  /**
   * Get the PersistenceMapper for a given type. If no mapper for this type is defined the mapper for type '*' will be returned
   * @param type The type of the object to get the PersistenceMapper for
   * @return A reference to the PersistenceMapper, null on error
   */
  function getMapper($type)
  {
    $mapper = null;
    // find type-specific mapper
    if (!array_key_exists($type, $this->_mapperObjects))
    {
      // first use
      // find mapper in configfile
      $parser = InifileParser::getInstance();
      if (($mapperClass = $parser->getValue($type, 'typemapping')) === false)
      {
        if (($mapperClass = $parser->getValue('*', 'typemapping')) === false) {
          throw new ConfigurationException("No PersistenceMapper found in configfile for type '".$type."' in section 'typemapping'");
        }
      }
      // find mapper class file
      $classFile = ObjectFactory::getClassfileFromConfig($mapperClass);
      // find mapper params
      $initParams = null;
      if (($initSection = $parser->getValue($mapperClass, 'initparams')) !== false)
      {
        if (($initParams = $parser->getSection($initSection)) === false) {
          throw new ConfigurationException("No '".$initSection."' section given in configfile.");
        }
      }

      // if connection is already opened reuse it
      $connectionKey = join(':', array_values($initParams));
      if (array_key_exists($connectionKey, $this->_dbConnections)) {
        $initParams = array('dbConnection' => &$this->_dbConnections[$connectionKey]);
      }
      // see if class is already instantiated and reuse it if possible
      $isAlreadyInUse = false;
      $mapperObjects = array_values($this->_mapperObjects);
      for ($i=0; $i<sizeof($mapperObjects); $i++)
      {
        if (strtolower(get_class($mapperObjects[$i])) == strtolower($mapperClass))
        {
          $this->_mapperObjects[$type] = &$mapperObjects[$i];
          $isAlreadyInUse = true;
          break;
        }
      }

      // instantiate class if needed
      if (!$isAlreadyInUse)
      {
        if (file_exists(BASE.$classFile))
        {
          require_once(BASE.$classFile);
          if ($initParams) {
            $mapperObj = new $mapperClass($initParams);
          }
          else {
            $mapperObj = new $mapperClass;
          }
          $this->_mapperObjects[$type] = &$mapperObj;
        }
        else {
          throw new ConfigurationException("Definition of PersistanceMapper ".$mapperClass." in '".$classFile."' not found.");
        }

        // lookup converter
        if (($converterClass = $parser->getValue($type, 'converter')) !== false ||
            ($converterClass = $parser->getValue('*', 'converter')) !== false)
        {
          $classFile = ObjectFactory::getClassfileFromConfig($converterClass);
          if ($classFile != null)
          {
            // instatiate class
            if (file_exists(BASE.$classFile))
            {
              require_once(BASE.$classFile);
              $converterObj = new $converterClass;
              $mapperObj->setDataConverter($converterObj);
            }
            else {
              throw new ConfigurationException("Definition of DataConverter ".$converterClass." in '".$classFile."' not found.");
            }
          }
        }
      }
    }

    if (array_key_exists($type, $this->_mapperObjects)) {
      $mapper = &$this->_mapperObjects[$type];
    }
    else {
      $mapper = &$this->_mapperObjects['*'];
    }
    // enable logging if desired
    if ($this->isLogging() && !$mapper->isLogging()) {
      $mapper->enableLogging($this->_logStrategy);
    }
    return $mapper;
  }
  /**
   * Explicitly set a PersistentMapper for a type
   * @param type The type to set the mapper for
   * @param mapper A reference to the mapper
   */
  function setMapper($type, PersistenceMapper $mapper)
  {
    $this->_mapperObjects[$type] = &$mapper;
  }
  /**
   * Store a connection for reuse
   * @param initParams The initParams used to initialize the conenction
   * @param connection A reference to the connection to save
   */
  function storeConnection(array $initParams, $connection)
  {
    if ($connection != null)
    {
      $connectionKey = join(':', array_values($initParams));
      $this->_dbConnections[$connectionKey] = $connection;
    }
  }
  /**
   * Set state to readonly. If set to true, PersistenceFacade will return only immutable
   * objects and save/delete methods are disabled.
   * @param isReadOnly True/False whether objects should be readonly or not
   */
  function setReadOnly($isReadOnly)
  {
    $this->_isReadOnly = $isReadOnly;
  }
  /**
   * Enable logging using a given OutputStrategy to log insert/update/delete actions to a file.
   * @param logStrategy The OutputStrategy to use.
   */
  function enableLogging($logStrategy)
  {
    $this->_logStrategy = $logStrategy;
    $this->_logging = true;
  }
  /**
   * Disable logging.
   */
  function disableLogging()
  {
    $this->_logging = false;
  }
  /**
   * Check if the PersistenceMapper is logging.
   * @return True/False whether the PersistenceMapper is logging.
   */
  function isLogging()
  {
    return $this->_logging;
  }
  /**
   * Set state to caching. If set to true, PersistenceFacade will cache all loaded objects
   * and returns cached instances when calling the PersistenceFacade::load method.
   * @param isCaching True/False whether objects should be chached or not
   */
  function setCaching($isCaching)
  {
    $this->_isCaching = $isCaching;
  }
  /**
   * Clear the object cache
   */
  function clearCache()
  {
    $this->_cache = array();
  }
  /**
   * Get cache key from the given parameters
   * @param oid The object id of the object
   * @param buildDepth One of the BUILDDEPTH constants
   * @param buildAttribs An assoziative array (@see PersistenceFacade::load)
   * @param buildTypes An array (@see PersistenceFacade::load)
   * @return The cache key string
   */
  function getCacheKey(ObjectId $oid, $buildDepth, array $buildAttribs, array $buildTypes)
  {
    $key = $oid->__toString().':'.$buildDepth.':';
    foreach($buildAttribs as $type => $attribs) {
      $key .= $type.'='.join(',', $attribs).':';
    }
    $key .= join(',', $buildTypes);
    return $key;
  }

  /**
   * ChangeListener interface implementation
   */

  /**
   * @see ChangeListener::getId()
   */
  function getId()
  {
    return __CLASS__;
  }
  /**
   * @see ChangeListener::valueChanged()
   */
  function valueChanged(PersistentObject $object, $name, $type, $oldValue, $newValue) {}
  /**
   * @see ChangeListener::propertyChanged()
   */
  function propertyChanged(PersistentObject $object, $name, $oldValue, $newValue) {}
  /**
   * @see ChangeListener::stateChanged()
   */
  function stateChanged(PersistentObject $object, $oldValue, $newValue)
  {
    // store the object id in the internal registry if the object was saved after creation
    if ($oldValue == STATE_NEW && $newValue == STATE_CLEAN)
    {
      $type = $object->getType();
      if (!array_key_exists($type, $this->_createdOIDs)) {
        $this->_createdOIDs[$type] = array();
      }
      array_push($this->_createdOIDs[$type], $object->getOID());
    }
  }
}
?>
