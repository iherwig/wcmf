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
require_once(WCMF_BASE."wcmf/lib/util/class.Log.php");
require_once(WCMF_BASE."wcmf/lib/util/class.InifileParser.php");
require_once(WCMF_BASE."wcmf/lib/output/class.OutputStrategy.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.ObjectId.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceMapper.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.PagingInfo.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.ChangeListener.php");
require_once(WCMF_BASE."wcmf/lib/core/class.ConfigurationException.php");

/**
 * @class PersistenceFacadeImpl
 * @ingroup Persistence
 * @brief Default PersistenceFacade implementation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PersistenceFacadeImpl extends PersistenceFacade implements ChangeListener
{
  private $_knownTypes = null;
  private $_mapperObjects = array();
  private $_createdOIDs = array();
  private $_cache = array();
  private $_logging = false;
  private $_logStrategy = null;
  private $_isReadOnly = false;
  private $_isCaching = false;
  private $_inTransaction = false;

  /**
   * Constructor
   */
  public function __construct()
  {
  }

  /**
   * @see PersistenceFacade::getKnownTypes()
   */
  public function getKnownTypes()
  {
    if ($this->_knownTypes == null)
    {
      $parser = InifileParser::getInstance();
      $this->_knownTypes = $parser->getSection('typemapping');
    }
    return array_keys($this->_knownTypes);
  }
  /**
   * @see PersistenceFacade::isKnownType()
   */
  public function isKnownType($type)
  {
    if ($this->_knownTypes == null) {
      self::getKnownTypes();
    }
    return (isset($this->_knownTypes[$type]) || isset($this->_knownTypes['*']));
  }
  /**
   * @see PersistenceFacade::load()
   */
  public function load(ObjectId $oid, $buildDepth=BUILDDEPTH_SINGLE, $buildAttribs=null, $buildTypes=null)
  {
    if ($buildDepth < 0 && !in_array($buildDepth, array(BUILDDEPTH_INFINITE, BUILDDEPTH_SINGLE))) {
      throw new IllegalArgumentException("Build depth not supported: $buildDepth", __FILE__, __LINE__);
    }
    $obj = null;

    // lookup the object in the cache
    if ($this->_isCaching)
    {
      $cacheKey = $this->getCacheKey($oid, $buildDepth, $buildAttribs, $buildTypes);
      if (isset($this->_cache[$cacheKey])) {
        $obj = $this->_cache[$cacheKey];
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
   * @see PersistenceFacade::create()
   */
  public function create($type, $buildDepth=BUILDDEPTH_SINGLE, $buildAttribs=null)
  {
    if ($buildDepth < 0 && !in_array($buildDepth, array(BUILDDEPTH_INFINITE, BUILDDEPTH_SINGLE, BUILDDEPTH_REQUIRED))) {
      throw new IllegalArgumentException("Build depth not supported: $buildDepth");
    }
    $obj = null;
    $mapper = $this->getMapper($type);
    if ($mapper != null)
    {
      $obj = $mapper->create($type, $buildDepth, $buildAttribs);

      // register as change listener to track the created oid, after save
      $obj->addChangeListener($this);
    }

    return $obj;
  }
  /**
   * @see PersistenceFacade::save()
   */
  public function save(PersistentObject $object)
  {
    if ($this->_isReadOnly) {
      return true;
    }
    $result = false;
    $mapper = $this->getMapper($object->getType());
    if ($mapper != null) {
      $result = $mapper->save($object);
    }
    return $result;
  }
  /**
   * @see PersistenceFacade::delete()
   */
  public function delete(ObjectId $oid)
  {
    if ($this->_isReadOnly) {
      return true;
    }
    $result = false;
    $mapper = $this->getMapper($oid->getType());
    if ($mapper != null) {
      $result = $mapper->delete($oid);
    }
    return $result;
  }
  /**
   * @see PersistenceFacade::getLastCreatedOID()
   */
  public function getLastCreatedOID($type)
  {
    if (isset($this->_createdOIDs[$type]) && sizeof($this->_createdOIDs[$type]) > 0) {
      return $this->_createdOIDs[$type][sizeof($this->_createdOIDs[$type])-1];
    }
    return null;
  }
  /**
   * @see PersistenceFacade::getOIDs()
   */
  public function getOIDs($type, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null)
  {
    $result = array();
    $mapper = $this->getMapper($type);
    if ($mapper != null) {
      $result = $mapper->getOIDs($type, $criteria, $orderby, $pagingInfo);
    }
    return $result;
  }
  /**
   * @see PersistenceFacade::getFirstOID()
   */
  public function getFirstOID($type, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null)
  {
    // TODO: use paging info to narrow result
    $oids = $this->getOIDs($type, $criteria, $orderby, $pagingInfo);
    if (sizeof($oids) > 0) {
      return $oids[0];
    }
    else {
      return null;
    }
  }
  /**
   * @see PersistenceFacade::loadObjects()
   */
  public function loadObjects($type, $buildDepth=BUILDDEPTH_SINGLE, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null,
    $buildAttribs=null, $buildTypes=null)
  {
    $result = array();
    $mapper = $this->getMapper($type);
    if ($mapper != null) {
      $result = $mapper->loadObjects($type, $buildDepth, $criteria, $orderby, $pagingInfo, $buildAttribs, $buildTypes);
    }
    return $result;
  }
  /**
   * @see PersistenceFacade::loadFirstObject()
   */
  public function loadFirstObject($type, $buildDepth=BUILDDEPTH_SINGLE, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null,
    $buildAttribs=null, $buildTypes=null)
  {
    // TODO: use paging info to narrow result
    $objects = $this->loadObjects($type, $buildDepth, $criteria, $orderby, $pagingInfo, $buildAttribs, $buildTypes);
    if (sizeof($objects) > 0) {
      return $objects[0];
    }
    else {
      return null;
    }
  }
  /**
   * @see PersistenceFacade::startTransaction()
   */
  public function startTransaction()
  {
    if (!$this->_inTransaction)
    {
      // log action
      if ($this->isLogging()) {
        Log::debug("Start Transaction", __CLASS__);
      }
      // end transaction for every mapper
      foreach ($this->getKnownTypes() as $type) {
        $mapper = $this->getMapper($type);
        $mapper->startTransaction();
      }
      $this->_inTransaction = true;
    }
  }
  /**
   * @see PersistenceFacade::commitTransaction()
   */
  public function commitTransaction()
  {
    if ($this->_inTransaction)
    {
      // log action
      if ($this->isLogging()) {
        Log::debug("Commit Transaction", __CLASS__);
      }
      // commit transaction for every mapper
      foreach ($this->getKnownTypes() as $type) {
        $mapper = $this->getMapper($type);
        $mapper->commitTransaction();
      }
      $this->_inTransaction = false;
    }
  }
  /**
   * @see PersistenceFacade::rollbackTransaction()
   */
  public function rollbackTransaction()
  {
    if ($this->_inTransaction)
    {
      if ($this->isLogging()) {
        Log::debug("Rollback Transaction", __CLASS__);
      }
      // rollback transaction for every mapper
      foreach ($this->getKnownTypes() as $type) {
        $mapper = $this->getMapper($type);
        $mapper->rollbackTransaction();
      }
      $this->_inTransaction = false;
    }
  }
  /**
   * @see PersistenceFacade::getMapper()
   */
  public function getMapper($type)
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
        $mapperObj = ObjectFactory::createInstance($mapperClass);
        $this->_mapperObjects[$type] = $mapperObj;

        // lookup converter (optional)
        if (($converterClass = $parser->getValue($type, 'converter')) !== false ||
            ($converterClass = $parser->getValue('*', 'converter')) !== false)
        {
          $converterObj = ObjectFactory::createInstance($converterClass);
          $mapperObj->setDataConverter($converterObj);
        }
      }
    }

    if (array_key_exists($type, $this->_mapperObjects)) {
      $mapper = $this->_mapperObjects[$type];
    }
    else {
      $mapper = $this->_mapperObjects['*'];
    }
    // enable logging if desired
    if ($this->isLogging() && !$mapper->isLogging()) {
      $mapper->enableLogging($this->_logStrategy);
    }
    return $mapper;
  }
  /**
   * @see PersistenceFacade::setMapper()
   */
  public function setMapper($type, PersistenceMapper $mapper)
  {
    $this->_mapperObjects[$type] = $mapper;
  }
  /**
   * @see PersistenceFacade::getMapperForConfigSection()
   */
  public function getMapperForConfigSection($configSection)
  {
    $mapper = null;
    $parser = InifileParser::getInstance();
    $initParamSection = $parser->getSection('initparams');
    $typeMappingSection = array_flip($parser->getSection('typemapping'));
    foreach ($initParamSection as $mapperClass => $curConfigSection)
    {
      if ($curConfigSection == $configSection) {
        $mapper = $this->getMapper($typeMappingSection[$mapperClass]);
        break;
      }
    }
    if ($mapper == null) {
      throw new ConfigurationException("No PersistenceMapper found in configfile for config section '".$configSection."'");
    }
    return $mapper;
  }
  /**
   * @see PersistenceFacade::setMapper()
   */
  public function enableLogging($logStrategy)
  {
    $this->_logStrategy = $logStrategy;
    $this->_logging = true;
  }
  /**
   * @see PersistenceFacade::disableLogging()
   */
  public function disableLogging()
  {
    $this->_logging = false;
  }
  /**
   * @see PersistenceFacade::isLogging()
   */
  public function isLogging()
  {
    return $this->_logging;
  }
  /**
   * @see PersistenceFacade::setReadOnly()
   */
  public function setReadOnly($isReadOnly)
  {
    $this->_isReadOnly = $isReadOnly;
  }
  /**
   * @see PersistenceFacade::setCaching()
   */
  public function setCaching($isCaching)
  {
    $this->_isCaching = $isCaching;
  }
  /**
   * @see PersistenceFacade::clearCache()
   */
  public function clearCache()
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
  protected function getCacheKey(ObjectId $oid, $buildDepth, array $buildAttribs, array $buildTypes)
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
  public function getId()
  {
    return __CLASS__;
  }
  /**
   * @see ChangeListener::valueChanged()
   */
  public function valueChanged(PersistentObject $object, $name, $oldValue, $newValue) {}
  /**
   * @see ChangeListener::propertyChanged()
   */
  public function propertyChanged(PersistentObject $object, $name, $oldValue, $newValue) {}
  /**
   * @see ChangeListener::stateChanged()
   */
  public function stateChanged(PersistentObject $object, $oldValue, $newValue)
  {
    // store the object id in the internal registry if the object was saved after creation
    if ($oldValue == PersistentObject::STATE_NEW && $newValue == PersistentObject::STATE_CLEAN)
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
