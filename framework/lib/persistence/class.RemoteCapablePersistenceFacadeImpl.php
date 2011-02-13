<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2010 wemove digital solutions GmbH
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
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacadeImpl.php");
require_once(WCMF_BASE."wcmf/lib/remoting/class.RemotingFacade.php");

/**
 * @class RemoteCapablePersistenceFacade
 * @ingroup Persistence
 * @brief RemoteCapablePersistenceFacade delegates local persistence operations to the
 * default PersistenceFacadeImpl and remote operations to a remote server.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RemoteCapablePersistenceFacadeImpl extends PersistenceFacadeImpl
{
  // constants
  const PROXY_OBJECTS_SESSION_VARNAME = 'RemoteCapablePersistenceFacadeImpl.proxyObjects';
  const REMOTE_OBJECTS_SESSION_VARNAME = 'RemoteCapablePersistenceFacadeImpl.remoteObjects';

  var $_isResolvingProxies = true;
  var $_isTranslatingValues = true;

  /**
   * Constructor
   */
  public function RemoteCapablePersistenceFacadeImpl()
  {
    // initialize session variables
    $session = SessionData::getInstance();
    if (!$session->exist(self::PROXY_OBJECTS_SESSION_VARNAME)) {
      $proxies = array();
      $session->set(self::PROXY_OBJECTS_SESSION_VARNAME, $proxies);
    }
    if (!$session->exist(self::REMOTE_OBJECTS_SESSION_VARNAME)) {
      $objs = array();
      $session->set(self::REMOTE_OBJECTS_SESSION_VARNAME, $objs);
    }
  }
  /**
   * Tell the PersistenceFacade implementation to resolve proxies or not.
   * @param isResolvingProxies True/False whether proxies should be resolved or not
   */
  public function setResolveProxies($isResolvingProxies)
  {
    $this->_isResolvingProxies = $isResolvingProxies;
  }
  /**
   * Check if the PersistenceFacade implementation is resolving proxies or not.
   * @return True/False whether proxies are resolved or not
   */
  public function isResolvingProxies()
  {
    return $this->_isResolvingProxies;
  }
  /**
   * Tell the PersistenceFacade implementation to translate remote values or not.
   * @param isTranslatingValues True/False whether values should be translated or not
   */
  public function setTranslatingValues($isTranslatingValues)
  {
    $this->_isTranslatingValues = $isTranslatingValues;
  }
  /**
   * Check if the PersistenceFacade implementation is translating remote values or not.
   * @return True/False whether values are tanslated or not
   */
  public function isTranslatingValues()
  {
    return $this->_isTranslatingValues;
  }
  /**
   * @see PersistenceFacade::load()
   */
  public function load(ObjectId $oid, $buildDepth, array $buildAttribs=array(), array $buildTypes=array())
  {
    if ($this->isResolvingProxies() && strlen($oid->getPrefix()) > 0) {
      // load real subject
      $obj = $this->loadRemoteObject($oid, $buildDepth);
    }
    else
    {
      $obj = parent::load($oid, $buildDepth, $buildAttribs, $buildTypes);
      if ($obj && $this->isResolvingProxies() && strlen($umi = $obj->getValue('umi')) > 0) {
        // store proxy for later reference
        $this->registerProxyObject($umi, $obj, $buildDepth);
        // load real subject
        $obj = $this->loadRemoteObject($umi, $buildDepth);
      }
    }
    return $obj;
  }
  /**
   * @see PersistenceFacade::create()
   */
  public function create($type, $buildDepth=BUILDDEPTH_SINGLE, array $buildAttribs=array())
  {
    $obj = parent::create($type, $buildDepth, $buildAttribs);
    return $obj;
  }
  /**
   * @see PersistenceFacade::save()
   */
  public function save(PersistentObject $object)
  {
    $oid = $object->getOID();
    if (strlen($oid->getPrefix()) > 0) {
      throw new PersistenceException("The remote object '".$object->getOID()."' is immutable.");
    }
    $result = parent::save($object);
    return $result;
  }
  /**
   * @see PersistenceFacade::delete()
   */
  public function delete(ObjectId $oid, $recursive=true)
  {
    if (strlen($oid->getPrefix()) > 0) {
      throw new PersistenceException("The remote object '".$oid."' is immutable.");
    }
    $result = parent::delete($oid, $recursive);
    return $result;
  }
  /**
   * @see PersistenceFacade::getOIDs()
   */
  public function getOIDs($type, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null)
  {
    $result = parent::getOIDs($type, $criteria, $orderby, $pagingInfo);
    return $result;
  }
  /**
   * @see PersistenceFacade::loadObjects()
   */
  public function loadObjects($type, $buildDepth, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null,
    array $buildAttribs=array(), array $buildTypes=array())
  {
    $tmpResult = parent::loadObjects($type, $buildDepth, $criteria, $orderby, $pagingInfo, $buildAttribs, $buildTypes);
    $result = array();
    foreach($tmpResult as $obj)
    {
      if ($obj && $this->isResolvingProxies() && strlen($umi = $obj->getValue('umi')) > 0) {
        // store proxy for later reference
        $this->registerProxyObject($umi, $obj, $buildDepth);
        // load real subject
        $result[] = $this->loadRemoteObject($umi, $buildDepth);
      }
      else {
        $result[] = $obj;
      }
    }
    return $result;
  }

  /**
   * Get the proxy object for a remote object.
   * This method makes sure that a proxy for the given remote object exists.
   * If it does not exist, it will be created.
   * @param umi The universal model id (oid with server prefix)
   * @return The proxy object.
   */
  protected function getProxyObject(ObjectId $umi, $buildDepth)
  {
    Log::debug("Get proxy object for: ".$umi, __CLASS__);

    // local objects don't have a proxy
    if (strlen($umi->getPrefix()) == 0) {
      return null;
    }

    // check if the proxy object was loaded already
    $proxy = $this->getRegisteredProxyObject($umi, $buildDepth);

    // search the proxy object if requested for the first time
    if (!$proxy)
    {
      $persistenceFacade = PersistenceFacade::getInstance();
      if ($persistenceFacade instanceof RemoteCapablePersistenceFacadeImpl) {
        $oldState = $persistenceFacade->isResolvingProxies();
        $persistenceFacade->setResolveProxies(false);
      }
      $proxy = $persistenceFacade->loadFirstObject($umi->getType(), $buildDepth, array($umi->getType().'.umi' => $umi->toString()));
      if ($persistenceFacade instanceof RemoteCapablePersistenceFacadeImpl) {
        $persistenceFacade->setResolveProxies($oldState);
      }
      if (!$proxy) {
        // the proxy has to be created
        Log::debug("Creating...", __CLASS__);
        $proxy = $persistenceFacade->create($umi->getType(), BUILDDEPTH_SINGLE);
        $proxy->setValue('umi', $umi);
        $proxy->save();
      }
      $this->registerProxyObject($umi, $proxy, $buildDepth);
    }
    Log::debug("Proxy oid: ".$proxy->getOID(), __CLASS__);
    return $proxy;
  }

  /**
   * Load the real subject of a proxy from the remote instance.
   * @param umi The universal model id (oid with server prefix)
   * @param buildDepth buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build (except BUILDDEPTH_REQUIRED)
   */
  protected function loadRemoteObject(ObjectId $umi, $buildDepth)
  {
    Log::debug("Resolve proxy object for: ".$umi, __CLASS__);

    // check if the remote object was loaded already
    $obj = $this->getRegisteredRemoteObject($umi, $buildDepth);

    // resolve the object if requested for the first time
    if (!$obj)
    {
      Log::debug("Retrieving...", __CLASS__);

      // determine remote oid
      $oid = new ObjectId($umi->getType(), $umi->getId());
      $serverKey = array_pop(preg_split('/:/', $umi->getPrefix()));

      // create the request
      $request = new Request(
        '',
        '',
        'display',
        array(
          'oid' => $oid->toString(),
          'depth' => "".$buildDepth,
          'omitMetaData' => true,
          'translateValues' => $this->_isTranslatingValues
        )
      );
      Log::debug("Request:\n".$request->toString(), __CLASS__);

      // do the remote call
      $facade = RemotingFacade::getInstance();
      $response = $facade->doCall($serverKey, $request);
      $obj = $response->getValue('node');
      if ($obj)
      {
        // set umis instead of oids
        $umiPrefix = $umi->getPrefix();
        $iter = new NodeIterator($obj);
        while (!$iter->isEnd())
        {
          $curNode = $iter->getCurrentNode();
          $oids = $this->makeUmis(array($curNode->getOID()), $umiPrefix);
          $curNode->setOID($oids[0]);
          // TODO implement this for new Node class
          /*
          $parentOIDs = $this->makeUmis($curNode->getProperty('parentoids'), $umiPrefix);
          $curNode->setProperty('parentoids', $parentOIDs);
          $childOIDs = $this->makeUmis($curNode->getProperty('childoids'), $umiPrefix);
          $curNode->setProperty('childoids', $childOIDs);
          */
          $iter->proceed();
        }
        // set the proxy oid as attribute
        $proxy = $this->getProxyObject($umi, $buildDepth);
        if ($proxy) {
          $proxyOID = $proxy->getOID();
          if (strlen($proxyOID->getPrefix()) > 0) {
            Log::debug("NOT A PROXY", __CLASS__);
          }
          $obj->setValue('_proxyOid', $proxyOID);

          // add proxy relations to the remote object
          $children = $proxy->getChildren();
          for($i=0, $count=sizeof($children); $i<$count; $i++) {
            $obj->addNode($children[$i]);
          }
          $parents = $proxy->getParents();
          for($i=0, $count=sizeof($parents); $i<$count; $i++) {
            $obj->addParent($parents[$i]);
          }
        }
        $this->registerRemoteObject($umi, $obj, $buildDepth);
      }
    }

    if (Log::isDebugEnabled(__CLASS__)) {
      if ($obj) {
        Log::debug("Resolved to: ".$obj->toString(), __CLASS__);
      }
      else {
        Log::debug("Could not resolve: ".$umi, __CLASS__);
      }
    }
    return $obj;
  }

  /**
   * Save a proxy object in the session.
   * @param umi The universal model id (oid with server prefix)
   * @param obj The proxy object.
   * @param buildDepth The depth the object was loaded.
   */
  protected function registerProxyObject(ObjectID $umi, PersistentObject $obj, $buildDepth)
  {
    $oid = $obj->getOID();
    if (strlen($oid->getPrefix()) > 0) {
      Log::debug("NOT A PROXY", __CLASS__);
      return;
    }
    $this->registerObject($umi, $obj, $buildDepth, self::PROXY_OBJECTS_SESSION_VARNAME);
  }

  /**
   * Save a remote object in the session.
   * @param umi The universal model id (oid with server prefix)
   * @param obj The remote object.
   * @param buildDepth The depth the object was loaded.
   */
  protected function registerRemoteObject(ObjectId $umi, PersistentObject $obj, $buildDepth)
  {
    // TODO: fix caching remote objects (invalidate cache entry, if an association to the object changes)
    return;

    $this->registerObject($umi, $obj, $buildDepth, self::REMOTE_OBJECTS_SESSION_VARNAME);
  }

  /**
   * Save a object in the given session variable.
   * @param umi The universal model id (oid with server prefix)
   * @param obj The object to register.
   * @param buildDepth The depth the object was loaded.
   * @param varName The session variable name.
   */
  protected function registerObject(ObjectId $umi, PersistentObject $obj, $buildDepth, $varName)
  {
    if ($buildDepth == 0) {
      $buildDepth=BUILDDEPTH_SINGLE;
    }
    // save the object in the session
    $session = SessionData::getInstance();
    $umiStr = $umi->toString();
    $objects = $session->get($varName);
    if (!isset($objects[$umiStr])) {
      $objects[$umiStr] = array();
    }
    $objects[$umiStr][$buildDepth] = $obj;
    $session->set($varName, $objects);

    // register class definitions in session
    $classFile = WCMF_BASE.ObjectFactory::getClassfile(get_class($obj));
    $mapperClassFile = WCMF_BASE.ObjectFactory::getClassfile(get_class($obj->getMapper()));
    $session->addClassDefinitions(array($classFile, $mapperClassFile));
  }

  /**
   * Get a proxy object from the session.
   * @param umi The universal model id (oid with server prefix)
   * @param buildDepth The requested build depth.
   * @return The proxy object or null if not found.
   */
  protected function getRegisteredProxyObject(ObjectId $umi, $buildDepth)
  {
    $proxy = $this->getRegisteredObject($umi, $buildDepth, self::PROXY_OBJECTS_SESSION_VARNAME);
    return $proxy;
  }

  /**
   * Get a remote object from the session.
   * @param umi The universal model id (oid with server prefix)
   * @param buildDepth The requested build depth.
   * @return The remote object or null if not found.
   */
  protected function getRegisteredRemoteObject(ObjectId $umi, $buildDepth)
  {
    $object = $this->getRegisteredObject($umi, $buildDepth, self::REMOTE_OBJECTS_SESSION_VARNAME);;
    return $object;
  }

  /**
   * Get a object from the given session variable.
   * @param umi The universal model id (oid with server prefix)
   * @param buildDepth The requested build depth.
   * @return The object or null if not found.
   */
  protected function getRegisteredObject(ObjectId $umi, $buildDepth, $varName)
  {
    if ($buildDepth == 0) {
      $buildDepth=BUILDDEPTH_SINGLE;
    }
    $session = SessionData::getInstance();
    $umiStr = $umi->toString();
    $objects = $session->get($varName);
    if (isset($objects[$umiStr]) && isset($objects[$umiStr][$buildDepth])) {
      return $objects[$umiStr][$buildDepth];
    }
    // check if an object with larger build depth was stored already
    if ($buildDepth == BUILDDEPTH_SINGLE) {
      $existingDepths = array_keys($objects[$umiStr]);
      foreach($existingDepths as $depth) {
        if ($depth > 0 || $depth == BUILDDEPTH_INFINITE) {
          return $objects[$umiStr][$depth];
        }
      }
    }
    return null;
  }
  /**
   * Replace all object ids in an array with the umis according to
   * the given umiPrefix.
   * @param oids The array of oids
   * @param umiPrefix The umi prefix
   * @return The array of umis
   */
  protected function makeUmis($oids, $umiPrefix)
  {
    $result = array();
    foreach ($oids as $oid)
    {
      if (strlen($oid->getPrefix()) == 0)
      {
        $umi = new ObjectId($oid->getType(), $oid->getId(), $umiPrefix);
        $result[] = $umi;
      }
    }
    return $result;
  }
}
?>
