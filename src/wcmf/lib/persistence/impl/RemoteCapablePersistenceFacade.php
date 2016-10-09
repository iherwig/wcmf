<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\persistence\impl;

use wcmf\lib\core\EventManager;
use wcmf\lib\core\LogManager;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\core\Session;
use wcmf\lib\model\NodeIterator;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\impl\DefaultPersistenceFacade;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\output\OutputStrategy;
use wcmf\lib\persistence\PagingInfo;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\service\RemotingServer;

/**
 * RemoteCapablePersistenceFacade delegates local persistence operations to the
 * default PersistenceFacadeImpl and remote operations to a remote server.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RemoteCapablePersistenceFacade extends DefaultPersistenceFacade {

  // constants
  const PROXY_OBJECTS_SESSION_VARNAME = 'RemoteCapablePersistenceFacadeImpl.proxyObjects';
  const REMOTE_OBJECTS_SESSION_VARNAME = 'RemoteCapablePersistenceFacadeImpl.remoteObjects';

  private $isResolvingProxies = true;
  private $isTranslatingValues = true;

  private $session = null;
  private $remotingServer = null;

  private static $logger = null;

  /**
   * Constructor
   * @param $eventManager
   * @param $logStrategy
   * @param $session
   */
  public function __construct(EventManager $eventManager,
          OutputStrategy $logStrategy,
          Session $session) {
    parent::__construct($eventManager, $logStrategy);
    $this->session = $session;
    if (self::$logger == null) {
      self::$logger = LogManager::getLogger(__CLASS__);
    }
    // initialize session variables
    if (!$this->session->exist(self::PROXY_OBJECTS_SESSION_VARNAME)) {
      $proxies = array();
      $this->session->set(self::PROXY_OBJECTS_SESSION_VARNAME, $proxies);
    }
    if (!$this->session->exist(self::REMOTE_OBJECTS_SESSION_VARNAME)) {
      $objs = array();
      $this->session->set(self::REMOTE_OBJECTS_SESSION_VARNAME, $objs);
    }
    $this->remotingServer = new RemotingServer();
    parent::__construct();
  }

  /**
   * Tell the PersistenceFacade implementation to resolve proxies or not.
   * @param $isResolvingProxies Boolean whether proxies should be resolved or not
   */
  public function setResolveProxies($isResolvingProxies) {
    $this->isResolvingProxies = $isResolvingProxies;
  }

  /**
   * Check if the PersistenceFacade implementation is resolving proxies or not.
   * @return Boolean whether proxies are resolved or not
   */
  public function isResolvingProxies() {
    return $this->isResolvingProxies;
  }

  /**
   * Tell the PersistenceFacade implementation to translate remote values or not.
   * @param $isTranslatingValues Boolean whether values should be translated or not
   */
  public function setTranslatingValues($isTranslatingValues) {
    $this->isTranslatingValues = $isTranslatingValues;
  }

  /**
   * Check if the PersistenceFacade implementation is translating remote values or not.
   * @return Boolean whether values are tanslated or not
   */
  public function isTranslatingValues() {
    return $this->isTranslatingValues;
  }

  /**
   * @see PersistenceFacade::load()
   */
  public function load(ObjectId $oid, $buildDepth=BuildDepth::SINGLE) {
    $obj = null;
    if ($this->isResolvingProxies() && strlen($oid->getPrefix()) > 0) {
      // load real subject
      $obj = $this->loadRemoteObject($oid, $buildDepth);
    }
    else {
      $obj = parent::load($oid, $buildDepth);
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
  public function create($type, $buildDepth=BuildDepth::SINGLE) {
    $obj = parent::create($type, $buildDepth);
    return $obj;
  }

  /**
   * @see PersistenceFacade::getOIDs()
   */
  public function getOIDs($type, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null) {
    $result = parent::getOIDs($type, $criteria, $orderby, $pagingInfo);
    return $result;
  }

  /**
   * @see PersistenceFacade::loadObjects()
   */
  public function loadObjects($type, $buildDepth=BuildDepth::SINGLE, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null) {

    $tmpResult = parent::loadObjects($type, $buildDepth, $criteria, $orderby, $pagingInfo);
    $result = array();
    foreach($tmpResult as $obj) {
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
   * @param $umi The universal model id (oid with server prefix)
   * @param $buildDepth buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build (except BuildDepth::REQUIRED)
   * @return The proxy object.
   */
  protected function getProxyObject(ObjectId $umi, $buildDepth) {
    self::$logger->debug("Get proxy object for: ".$umi);

    // local objects don't have a proxy
    if (strlen($umi->getPrefix()) == 0) {
      return null;
    }

    // check if the proxy object was loaded already
    $proxy = $this->getRegisteredProxyObject($umi, $buildDepth);

    // search the proxy object if requested for the first time
    if (!$proxy) {
      $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
      $isRemoteCapableFacade = ($persistenceFacade instanceof RemoteCapablePersistenceFacadeImpl);
      $oldState = true;
      if ($isRemoteCapableFacade) {
        $oldState = $persistenceFacade->isResolvingProxies();
        $persistenceFacade->setResolveProxies(false);
      }
      $proxy = $persistenceFacade->loadFirstObject($umi->getType(), $buildDepth, array($umi->getType().'.umi' => $umi->toString()));
      if ($isRemoteCapableFacade) {
        $persistenceFacade->setResolveProxies($oldState);
      }
      if (!$proxy) {
        // the proxy has to be created
        self::$logger->debug("Creating...");
        $proxy = $persistenceFacade->create($umi->getType(), BuildDepth::SINGLE);
        $proxy->setValue('umi', $umi);
        $proxy->save();
      }
      $this->registerProxyObject($umi, $proxy, $buildDepth);
    }
    self::$logger->debug("Proxy oid: ".$proxy->getOID());
    return $proxy;
  }

  /**
   * Load the real subject of a proxy from the remote instance.
   * @param $umi The universal model id (oid with server prefix)
   * @param $buildDepth buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build (except BuildDepth::REQUIRED)
   */
  protected function loadRemoteObject(ObjectId $umi, $buildDepth) {
    self::$logger->debug("Resolve proxy object for: ".$umi);

    // check if the remote object was loaded already
    $obj = $this->getRegisteredRemoteObject($umi, $buildDepth);

    // resolve the object if requested for the first time
    if (!$obj) {
      self::$logger->debug("Retrieving...");

      // determine remote oid
      $oid = new ObjectId($umi->getType(), $umi->getId());
      $serverKey = array_pop(preg_split('/:/', $umi->getPrefix()));

      // create the request
      $request = ObjectFactory::getNewInstance('request');
      $request->setAction('display');
      $request->setValues(
        array(
          'oid' => $oid->toString(),
          'depth' => "".$buildDepth,
          'omitMetaData' => true,
          'translateValues' => $this->isTranslatingValues
        )
      );
      self::$logger->debug("Request:\n".$request->toString());

      // do the remote call
      $response = $this->remotingServer->doCall($serverKey, $request);
      $obj = $response->getValue('node');
      if ($obj) {
        // set umis instead of oids
        $umiPrefix = $umi->getPrefix();
        $iter = new NodeIterator($obj);
        foreach($iter as $oid => $curNode) {
          $oids = $this->makeUmis(array($curNode->getOID()), $umiPrefix);
          $curNode->setOID($oids[0]);
          // TODO implement this for new Node class
          /*
          $parentOIDs = $this->makeUmis($curNode->getProperty('parentoids'), $umiPrefix);
          $curNode->setProperty('parentoids', $parentOIDs);
          $childOIDs = $this->makeUmis($curNode->getProperty('childoids'), $umiPrefix);
          $curNode->setProperty('childoids', $childOIDs);
          */
        }
        // set the proxy oid as attribute
        $proxy = $this->getProxyObject($umi, $buildDepth);
        if ($proxy) {
          $proxyOID = $proxy->getOID();
          if (strlen($proxyOID->getPrefix()) > 0) {
            self::$logger->debug("NOT A PROXY");
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

    if (self::$logger->isDebugEnabled()) {
      if ($obj) {
        self::$logger->debug("Resolved to: ".$obj->toString());
      }
      else {
        self::$logger->debug("Could not resolve: ".$umi);
      }
    }
    return $obj;
  }

  /**
   * Save a proxy object in the session.
   * @param $umi The universal model id (oid with server prefix)
   * @param $obj The proxy object.
   * @param $buildDepth The depth the object was loaded.
   */
  protected function registerProxyObject(ObjectID $umi, PersistentObject $obj, $buildDepth) {
    $oid = $obj->getOID();
    if (strlen($oid->getPrefix()) > 0) {
      self::$logger->debug("NOT A PROXY");
      return;
    }
    $this->registerObject($umi, $obj, $buildDepth, self::PROXY_OBJECTS_SESSION_VARNAME);
  }

  /**
   * Save a remote object in the session.
   * @param $umi The universal model id (oid with server prefix)
   * @param $obj The remote object.
   * @param $buildDepth The depth the object was loaded.
   */
  protected function registerRemoteObject(ObjectId $umi, PersistentObject $obj, $buildDepth) {
    // TODO: fix caching remote objects (invalidate cache entry, if an association to the object changes)
    return;

    $this->registerObject($umi, $obj, $buildDepth, self::REMOTE_OBJECTS_SESSION_VARNAME);
  }

  /**
   * Save a object in the given session variable.
   * @param $umi The universal model id (oid with server prefix)
   * @param $obj The object to register.
   * @param $buildDepth The depth the object was loaded.
   * @param $varName The session variable name.
   */
  protected function registerObject(ObjectId $umi, PersistentObject $obj, $buildDepth, $varName) {
    if ($buildDepth == 0) {
      $buildDepth=BuildDepth::SINGLE;
    }
    // save the object in the session
    $umiStr = $umi->toString();
    $objects = $this->session->get($varName);
    if (!isset($objects[$umiStr])) {
      $objects[$umiStr] = array();
    }
    $objects[$umiStr][$buildDepth] = $obj;
    $this->session->set($varName, $objects);
  }

  /**
   * Get a proxy object from the session.
   * @param $umi The universal model id (oid with server prefix)
   * @param $buildDepth The requested build depth.
   * @return The proxy object or null if not found.
   */
  protected function getRegisteredProxyObject(ObjectId $umi, $buildDepth) {
    $proxy = $this->getRegisteredObject($umi, $buildDepth, self::PROXY_OBJECTS_SESSION_VARNAME);
    return $proxy;
  }

  /**
   * Get a remote object from the session.
   * @param $umi The universal model id (oid with server prefix)
   * @param $buildDepth The requested build depth.
   * @return The remote object or null if not found.
   */
  protected function getRegisteredRemoteObject(ObjectId $umi, $buildDepth) {
    $object = $this->getRegisteredObject($umi, $buildDepth, self::REMOTE_OBJECTS_SESSION_VARNAME);
    return $object;
  }

  /**
   * Get a object from the given session variable.
   * @param $umi The universal model id (oid with server prefix)
   * @param $buildDepth The requested build depth.
   * @param $varName The session variable name
   * @return The object or null if not found.
   */
  protected function getRegisteredObject(ObjectId $umi, $buildDepth, $varName) {
    if ($buildDepth == 0) {
      $buildDepth=BuildDepth::SINGLE;
    }
    $umiStr = $umi->toString();
    $objects = $this->session->get($varName);
    if (isset($objects[$umiStr]) && isset($objects[$umiStr][$buildDepth])) {
      return $objects[$umiStr][$buildDepth];
    }
    // check if an object with larger build depth was stored already
    if ($buildDepth == BuildDepth::SINGLE) {
      $existingDepths = array_keys($objects[$umiStr]);
      foreach($existingDepths as $depth) {
        if ($depth > 0 || $depth == BuildDepth::INFINITE) {
          return $objects[$umiStr][$depth];
        }
      }
    }
    return null;
  }

  /**
   * Replace all object ids in an array with the umis according to
   * the given umiPrefix.
   * @param $oids The array of oids
   * @param $umiPrefix The umi prefix
   * @return The array of umis
   */
  protected function makeUmis($oids, $umiPrefix) {
    $result = array();
    foreach ($oids as $oid) {
      if (strlen($oid->getPrefix()) == 0) {
        $umi = new ObjectId($oid->getType(), $oid->getId(), $umiPrefix);
        $result[] = $umi;
      }
    }
    return $result;
  }
}
?>
