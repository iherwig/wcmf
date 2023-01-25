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
use wcmf\lib\util\StringUtil;

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
  const REGISTRY_VAR = 'registry';

  // persistent iterator id
  const ITERATOR_ID_VAR = 'BatchDisplayController.iteratorid';

  // default values, maybe overriden by corresponding request values (see above)
  const NODES_PER_CALL = 50;

  /**
   * @see Controller::initialize()
   */
  public function initialize(Request $request, Response $response) {
    // initialize controller
    if ($request->getAction() != 'continue') {
      $session = $this->getSession();

      // set defaults (will be stored with first request)
      if (!$request->hasValue('nodesPerCall')) {
        $request->setValue('nodesPerCall', self::NODES_PER_CALL);
      }
      if (!$request->hasValue('translateValues')) {
        $request->setValue('translateValues', true);
      }

      // initialize session variables
      $this->setLocalSessionValue(self::REGISTRY_VAR, []);

      // reset iterator
      PersistentIterator::reset(self::ITERATOR_ID_VAR, $session);
    }
    // initialize parent controller after default request values are set
    parent::initialize($request, $response);
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
          ['invalidOids' => [$request->getValue('oid')]]));
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
      return ['name' => $this->getMessage()->getText('Loading'),
          'size' => 1, 'oids' => [1], 'callback' => 'startProcess'];
    }
    else {
      return null;
    }
  }

  /**
   * Initialize the iterator (object ids parameter will be ignored)
   * @param $oids The object ids to process
   */
  protected function startProcess($oids) {
    $session = $this->getSession();
    $persistenceFacade = $this->getPersistenceFacade();

    // restore the request oid from session
    $nodeOID = ObjectId::parse($this->getRequestValue('oid'));

    // do the action
    $iterator = new PersistentIterator(self::ITERATOR_ID_VAR, $persistenceFacade, $session, $nodeOID);
    $iterator->save();

    // display the first node in order to reduce the number of calls
    $this->loadNode($iterator->current());

    $iterator->next();

    // proceed if nodes are left
    if ($iterator->valid()) {
      $iterator->save();

      $name = $this->getMessage()->getText('Loading tree: continue with %0%',
              [$iterator->current()]);
      $this->addWorkPackage($name, 1, [null], 'loadNodes');
    }
    else {
      // set the result and finish
      $this->endProcess();
    }
  }

  /**
   * Load nodes provided by the persisted iterator (object ids parameter will be ignored)
   * @param $oids The object ids to process
   */
  protected function loadNodes($oids) {
    $session = $this->getSession();
    $persistenceFacade = $this->getPersistenceFacade();

    // check for iterator in session
    $iterator = PersistentIterator::load(self::ITERATOR_ID_VAR, $persistenceFacade, $session);

    // no iterator, finish
    if ($iterator == null || !$iterator->valid()) {
      // set the result and finish
      $this->endProcess();
    }

    // process nodes
    $counter = 0;
    $nodesPerCall = $this->getRequestValue('nodesPerCall');
    while ($iterator->valid() && $counter < $nodesPerCall) {
      $currentOID = $iterator->current();
      $this->loadNode($currentOID);

      $iterator->next();
      $counter++;
    }

    // decide what to do next
    if ($iterator->valid()) {
      // proceed with current iterator
      $iterator->save();

      $name = $this->getMessage()->getText('Loading tree: continue with %0%',
              [$iterator->current()]);
      $this->addWorkPackage($name, 1, [null], 'loadNodes');
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
    // nothing to do, nodes are added to response during the process already
  }

  /**
   * Load the node with the given object id and assign it to the response.
   * @param $oid The object id of the node to copy
   */
  protected function loadNode(ObjectId $oid) {
    // check if we already loaded the node
    if ($this->isRegistered($oid)) {
      return;
    }
    $persistenceFacade = $this->getPersistenceFacade();

    // restore the request values from session
    $language = $this->getRequestValue('language');
    $translateValues = StringUtil::getBoolean($this->getRequestValue('translateValues'));

    // load the node
    $node = $persistenceFacade->load($oid);
    if ($node == null) {
      throw new PersistenceException("Can't load node '".$oid."'");
    }

    // translate all nodes to the requested language if requested
    if ($this->isLocalizedRequest()) {
      $localization = $this->getLocalization();
      $node = $localization->loadTranslation($node, $language, true, true);
    }

    // translate values if requested
    if ($translateValues) {
      $nodes = [$node];
      if ($this->isLocalizedRequest()) {
        NodeUtil::translateValues($nodes, $language);
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
    $registry = $this->getLocalSessionValue(self::REGISTRY_VAR);
    $registry[] = $oid->__toString();
    $this->setLocalSessionValue(self::REGISTRY_VAR, $registry);
  }

  /**
   * Check if an object id is registered in the registry
   * @param $oid The object id to check
   * @return bool whether the object id is registered or not
   */
  protected function isRegistered(ObjectId $oid) {
    $registry = $this->getLocalSessionValue(self::REGISTRY_VAR);
    return in_array($oid->__toString(), $registry);
  }

  /**
   * Add a given node to the list variable of the response
   * @param $node The Node instance to add
   */
  protected function addNodeToResponse(Node $node) {
    $response = $this->getResponse();
    if (!$response->hasValue('list')) {
      $objects = [];
      $response->setValue('list', $objects);
    }

    $objects = $response->getValue('list');
    $objects[] = $node;
    $response->setValue('list', $objects);
  }
}
?>