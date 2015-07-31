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
namespace wcmf\application\controller;

use wcmf\application\controller\BatchController;
use wcmf\lib\model\Node;
use wcmf\lib\model\NodeUtil;
use wcmf\lib\model\PersistentIterator;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceException;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\Controller;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;

/**
 * BatchDisplayController is used to load a tree of Node instances recursivly and
 * return them in lists of a given size. The reconstruction of the tree must be
 * handled by the client.
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ _default_ </div>
 * <div>
 * Load the Nodes.
 * | Parameter                 | Description
 * |---------------------------|-------------------------
 * | _in_ / _out_  `oid`       | The object id of the Node to start loading from
 * | _in_ `translateValues`    | Boolean whether list values should be translated to their display values (optional, default: _true_)
 * | _in_ `nodesPerCall`       | The number of Node instances to load in one call (default: 50)
 * | _out_ `list`              | Array of Node instances
 * </div>
 * </div>
 *
 * For additional actions and parameters see [BatchController actions](@ref BatchController).
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class BatchDisplayController extends BatchController {

  // session name constants
  private $REQUEST = 'BatchDisplayController.request';
  private $REGISTRY = 'BatchDisplayController.registry';
  private $ITERATOR_ID = 'BatchDisplayController.iteratorid';

  // default values, maybe overriden by corresponding request values (see above)
  private $_NODES_PER_CALL = 50;

  /**
   * @see Controller::initialize()
   */
  public function initialize(Request $request, Response $response) {
    parent::initialize($request, $response);

    // initialize controller
    if ($request->getAction() != 'continue') {
      $session = $this->getInstance('session');

      // set defaults
      if (!$request->hasValue('nodesPerCall')) {
        $request->setValue('nodesPerCall', $this->_NODES_PER_CALL);
      }
      if (!$request->hasValue('translateValues')) {
        $request->setValue('translateValues', true);
      }

      // store request in session
      $session->set($this->REQUEST, $request);
      $reg = array();
      $session->set($this->REGISTRY, $reg);
    }
  }

  /**
   * @see Controller::validate()
   */
  protected function validate() {
    $request = $this->getRequest();
    $response = $this->getResponse();
    if ($request->getAction() != 'continue') {
      $oid = ObjectId::parse($request->getValue('oid'));
      if(!$oid) {
        $response->addError(ApplicationError::get('OID_INVALID',
          array('invalidOids' => array($request->getValue('oid')))));
        return false;
      }
      if (!$this->checkLanguageParameter()) {
        return false;
      }
    }
    // do default validation
    return parent::validate();
  }

  /**
   * @see BatchController::getWorkPackage()
   */
  protected function getWorkPackage($number) {
    if ($number == 0) {
      return array('name' => $this->getInstance('message')->getText('Loading'),
          'size' => 1, 'oids' => array(1), 'callback' => 'startProcess');
    }
    else {
      return null;
    }
  }

  /**
   * Initialize the iterator (oids parameter will be ignored)
   * @param $oids The oids to process
   */
  protected function startProcess($oids) {
    $session = $this->getInstance('session');

    // restore the request from session
    $request = $session->get($this->REQUEST);
    $nodeOID = ObjectId::parse($request->getValue('oid'));

    // do the action
    $iterator = new PersistentIterator($nodeOID);
    $iteratorID = $iterator->save();
    $session->set($this->ITERATOR_ID, $iteratorID);

    // display the first node in order to reduce the number of calls
    $this->loadNode($iterator->current());

    $iterator->next();

    // proceed if nodes are left
    if ($iterator->valid()) {
      $iteratorID = $iterator->save();
      $session->set($this->ITERATOR_ID, $iteratorID);

      $name = $this->getInstance('message')->getText('Loading tree: continue with %0%',
              array($iterator->current()));
      $this->addWorkPackage($name, 1, array(null), 'loadNodes');
    }
    else {
      // set the result and finish
      $this->endProcess();
    }
  }

  /**
   * Load nodes provided by the persisted iterator (oids parameter will be ignored)
   * @param $oids The oids to process
   */
  protected function loadNodes($oids) {
    $session = $this->getInstance('session');

    // restore the request from session
    $request = $session->get($this->REQUEST);

    // check for iterator in session
    $iterator = null;
    $iteratorID = $session->get($this->ITERATOR_ID);
    if ($iteratorID != null) {
      $iterator = PersistentIterator::load($iteratorID);
    }

    // no iterator, finish
    if ($iterator == null) {
      // set the result and finish
      $this->endProcess();
    }

    // process _NODES_PER_CALL nodes
    $counter = 0;
    while ($iterator->valid() && $counter < $request->getValue('nodesPerCall')) {
      $currentOID = $iterator->current();
      $this->loadNode($currentOID);

      $iterator->next();
      $counter++;
    }

    // decide what to do next
    if ($iterator->valid()) {
      // proceed with current iterator
      $iteratorID = $iterator->save();
      $session->set($this->ITERATOR_ID, $iteratorID);

      $name = $this->getInstance('message')->getText('Loading tree: continue with %0%',
              array($iterator->current()));
      $this->addWorkPackage($name, 1, array(null), 'loadNodes');
    }
    else {
      // set the result and finish
      $this->endProcess();
    }
  }

  /**
   * Finish the process and set the result
   */
  protected function endProcess() {
    $session = $this->getInstance('session');

    // clear session variables
    $tmp = null;
    $session->set($this->REQUEST, $tmp);
    $session->set($this->REGISTRY, $tmp);
    $session->set($this->ITERATOR_ID, $tmp);
  }

  /**
   * Load the node with the given object id and assign it to the response.
   * @param $oid The oid of the node to copy
   */
  protected function loadNode(ObjectId $oid) {
    // check if we already loaded the node
    if ($this->isRegistered($oid)) {
      return;
    }
    $persistenceFacade = $this->getInstance('persistenceFacade');
    $session = $this->getInstance('session');

    // restore the request from session
    $request = $session->get($this->REQUEST);

    // load the node
    $node = $persistenceFacade->load($oid);
    if ($node == null) {
      throw new PersistenceException("Can't load node '".$oid."'");
    }

    // translate all nodes to the requested language if requested
    if ($this->isLocalizedRequest()) {
      $localization = $this->getInstance('localization');
      $node = $localization->loadTranslation($node, $request->getValue('language'), true, true);
    }

    // translate values if requested
    if ($request->getBooleanValue('translateValues')) {
      $nodes = array($node);
      if ($this->isLocalizedRequest()) {
        NodeUtil::translateValues($nodes, $request->getValue('language'));
      }
      else {
        NodeUtil::translateValues($nodes);
      }
    }

    // assign it to the response
    $this->addNodeToResponse($node);

    $this->register($oid);

    $logger = $this->getLogger();
    if ($logger->isInfoEnabled()) {
      $logger->info("Loaded: ".$node->getOID());
    }
    if ($logger->isDebugEnabled()) {
      $logger->debug($node->toString());
    }
  }

  /**
   * Register an object id in the registry
   * @param $oid The object id to register
   */
  protected function register(ObjectId $oid) {
    $session = $this->getInstance('session');
    $registry = $session->get($this->REGISTRY);
    $registry[] = $oid;
    $session->set($this->REGISTRY, $registry);
  }

  /**
   * Check if an object id is registered in the registry
   * @param $oid The object id to check
   * @return Boolean whether the oid is registered or not
   */
  protected function isRegistered(ObjectId $oid) {
    $session = $this->getInstance('session');
    $registry = $session->get($this->REGISTRY);

    return in_array($oid, $registry);
  }

  /**
   * Add a given node to the list variable of the response
   * @param $node A reference to the node to add
   */
  protected function addNodeToResponse(Node $node) {
    $response = $this->getResponse();
    if (!$response->hasValue('list')) {
      $objects = array();
      $response->setValue('list', $objects);
    }

    $objects = $response->getValue('list');
    $objects[] = $node;
    $response->setValue('list', $objects);
  }
}
?>