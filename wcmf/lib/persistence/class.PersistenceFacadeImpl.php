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
require_once(WCMF_BASE."wcmf/lib/output/class.IOutputStrategy.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.IPersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.IPersistenceMapper.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.IChangeListener.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.ITransaction.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.Transaction.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.ObjectId.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.PagingInfo.php");
require_once(WCMF_BASE."wcmf/lib/core/class.ConfigurationException.php");

/**
 * @class PersistenceFacadeImpl
 * @ingroup Persistence
 * @brief Default PersistenceFacade implementation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PersistenceFacadeImpl implements IPersistenceFacade, IChangeListener
{
  private $_knownTypes = null;
  private $_mapperObjects = array();
  private $_createdOIDs = array();
  private $_logging = false;
  private $_logStrategy = null;
  private $_isReadOnly = false;
  private $_currentTransaction = null;

  /**
   * Constructor
   */
  public function __construct() {}

  /**
   * @see IPersistenceFacade::getKnownTypes()
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
   * @see IPersistenceFacade::isKnownType()
   */
  public function isKnownType($type)
  {
    if ($this->_knownTypes == null) {
      self::getKnownTypes();
    }
    return (isset($this->_knownTypes[$type]) || isset($this->_knownTypes['*']));
  }
  /**
   * @see IPersistenceFacade::load()
   */
  public function load(ObjectId $oid, $buildDepth=BUILDDEPTH_SINGLE, $buildAttribs=null, $buildTypes=null)
  {
    if ($buildDepth < 0 && !in_array($buildDepth, array(BUILDDEPTH_INFINITE, BUILDDEPTH_SINGLE))) {
      throw new IllegalArgumentException("Build depth not supported: $buildDepth", __FILE__, __LINE__);
    }
    $this->checkArrayParameter($buildAttribs, 'buildAttribs');
    $this->checkArrayParameter($buildTypes, 'buildTypes');

    $obj = null;

    // check if the object is already part of the transaction
    $transaction = $this->getTransaction();
    // extract type specific build attribs
    $attribs = null;
    if ($buildAttribs !== null && isset($buildAttribs[$oid->getType()])) {
      $attribs = $buildAttribs[$oid->getType()];
    }
    $obj = $transaction->getLoaded($oid, $attribs);

    // if not cached, load
    if ($obj == null)
    {
      $mapper = $this->getMapper($oid->getType());
      if ($mapper != null) {
        $obj = $mapper->load($oid, $buildDepth, $buildAttribs, $buildTypes);
      }
      if ($obj != null) {
        // prepare the object (readonly/locked)
        if ($this->_isReadOnly) {
          $obj->setImmutable();
        }
      }
    }
    return $obj;
  }
  /**
   * @see IPersistenceFacade::create()
   */
  public function create($type, $buildDepth=BUILDDEPTH_SINGLE, $buildAttribs=null)
  {
    if ($buildDepth < 0 && !in_array($buildDepth, array(BUILDDEPTH_INFINITE, BUILDDEPTH_SINGLE, BUILDDEPTH_REQUIRED))) {
      throw new IllegalArgumentException("Build depth not supported: $buildDepth");
    }
    $this->checkArrayParameter($buildAttribs, 'buildAttribs');

    $obj = null;
    $mapper = $this->getMapper($type);
    if ($mapper != null)
    {
      $obj = $mapper->create($type, $buildDepth, $buildAttribs);

      // register the object with the transaction, if it is active
      $transaction = $this->getTransaction();
      if ($transaction->isActive()) {
        $transaction->registerNew($obj);
        $obj->addChangeListener($transaction);
      }

      // register as change listener to track the created oid, after save
      $obj->addChangeListener($this);
    }

    return $obj;
  }
  /**
   * @see IPersistenceFacade::getLastCreatedOID()
   */
  public function getLastCreatedOID($type)
  {
    if (isset($this->_createdOIDs[$type]) && sizeof($this->_createdOIDs[$type]) > 0) {
      return $this->_createdOIDs[$type][sizeof($this->_createdOIDs[$type])-1];
    }
    return null;
  }
  /**
   * @see IPersistenceFacade::getOIDs()
   */
  public function getOIDs($type, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null)
  {
    $this->checkArrayParameter($criteria, 'criteria');
    $this->checkArrayParameter($orderby, 'orderby');

    $result = array();
    $mapper = $this->getMapper($type);
    if ($mapper != null) {
      $result = $mapper->getOIDs($type, $criteria, $orderby, $pagingInfo);
    }
    return $result;
  }
  /**
   * @see IPersistenceFacade::getFirstOID()
   */
  public function getFirstOID($type, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null)
  {
    if ($pagingInfo == null) {
      $pagingInfo = new PagingInfo(1);
    }
    $oids = $this->getOIDs($type, $criteria, $orderby, $pagingInfo);
    if (sizeof($oids) > 0) {
      return $oids[0];
    }
    else {
      return null;
    }
  }
  /**
   * @see IPersistenceFacade::loadObjects()
   */
  public function loadObjects($type, $buildDepth=BUILDDEPTH_SINGLE, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null,
    $buildAttribs=null, $buildTypes=null)
  {
    $this->checkArrayParameter($criteria, 'criteria');
    $this->checkArrayParameter($orderby, 'orderby');

    $result = array();
    $mapper = $this->getMapper($type);
    if ($mapper != null)
    {
      $result = $mapper->loadObjects($type, $buildDepth, $criteria, $orderby, $pagingInfo, $buildAttribs, $buildTypes);
      foreach($result as $obj) {
        if ($obj != null) {
          // prepare the object (readonly/locked)
          if ($this->_isReadOnly) {
            $obj->setImmutable();
          }
        }
      }
    }
    return $result;
  }
  /**
   * @see IPersistenceFacade::loadFirstObject()
   */
  public function loadFirstObject($type, $buildDepth=BUILDDEPTH_SINGLE, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null,
    $buildAttribs=null, $buildTypes=null)
  {
    if ($pagingInfo == null) {
      $pagingInfo = new PagingInfo(1);
    }
    $objects = $this->loadObjects($type, $buildDepth, $criteria, $orderby, $pagingInfo, $buildAttribs, $buildTypes);
    if (sizeof($objects) > 0) {
      return $objects[0];
    }
    else {
      return null;
    }
  }
  /**
   * @see IPersistenceFacade::getTransaction()
   */
  public function getTransaction()
  {
    if ($this->_currentTransaction == null) {
      $this->_currentTransaction = new Transaction();
    }
    return $this->_currentTransaction;
  }
  /**
   * @see IPersistenceFacade::getMapper()
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
   * @see IPersistenceFacade::setMapper()
   */
  public function setMapper($type, IPersistenceMapper $mapper)
  {
    $this->_mapperObjects[$type] = $mapper;
  }
  /**
   * @see IPersistenceFacade::getMapperForConfigSection()
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
   * @see IPersistenceFacade::setMapper()
   */
  public function enableLogging($logStrategy)
  {
    $this->_logStrategy = $logStrategy;
    $this->_logging = true;
  }
  /**
   * @see IPersistenceFacade::disableLogging()
   */
  public function disableLogging()
  {
    $this->_logging = false;
  }
  /**
   * @see IPersistenceFacade::isLogging()
   */
  public function isLogging()
  {
    return $this->_logging;
  }
  /**
   * @see IPersistenceFacade::setReadOnly()
   */
  public function setReadOnly($isReadOnly)
  {
    $this->_isReadOnly = $isReadOnly;
  }
  /**
   * Check if the given value is either null or an array and
   * throw an exception if not
   * @param param The parameter
   * @param name The name of the parameter (used in the exception text)
   */
  private function checkArrayParameter($param, $paramName)
  {
    if ($param != null && !is_array($param)) {
      throw new IllegalArgumentException("The parameter '".$paramName.
              "' is expected to be null or an array");
    }
  }

  /**
   * ChangeListener interface implementation
   */

  /**
   * @see IChangeListener::getId()
   */
  public function getId()
  {
    return __CLASS__;
  }
  /**
   * @see IChangeListener::valueChanged()
   */
  public function valueChanged(PersistentObject $object, $name, $oldValue, $newValue) {}
  /**
   * @see IChangeListener::propertyChanged()
   */
  public function propertyChanged(PersistentObject $object, $name, $oldValue, $newValue) {}
  /**
   * @see IChangeListener::stateChanged()
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
