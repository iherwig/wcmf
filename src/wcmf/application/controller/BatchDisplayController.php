<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
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
 */
namespace wcmf\application\controller;

use wcmf\application\controller\BatchController;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\i18n\Message;
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
 * BatchDisplayController is a controller that loads a tree of Nodes recursivly and
 * returns the Nodes in lists of a given size. The reconstruction of the tree must
 * be handled by the client.
 *
 * <b>Input actions:</b>
 * - see BatchController
 *
 * <b>Output actions:</b>
 * - see BatchController
 *
 * @param[in,out] oid The oid of the Node to start loading from
 * @param[in] translateValues Boolean. If true, list values will be translated using Control::translateValue. If not given,
 *                        all values will be returned as is, default: true
 * @param[in] nodes_per_call The number of nodes to process in one call, default: 50
 * @param[out] list An array of Nodes
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
      $session = ObjectFactory::getInstance('session');

      // set defaults
      if (!$request->hasValue('nodes_per_call')) {
        $request->setValue('nodes_per_call', $this->_NODES_PER_CALL);
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
      return array('name' => Message::get('Loading'), 'size' => 1, 'oids' => array(1), 'callback' => 'startProcess');
    }
    else {
      return null;
    }
  }

  /**
   * Initialize the iterator (oids parameter will be ignored)
   * @param oids The oids to process
   */
  protected function startProcess($oids) {
    $session = ObjectFactory::getInstance('session');

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

      $name = Message::get('Loading tree: continue with %0%', array($iterator->current()));
      $this->addWorkPackage($name, 1, array(null), 'loadNodes');
    }
    else {
      // set the result and finish
      $this->endProcess();
    }
  }

  /**
   * Load nodes provided by the persisted iterator (oids parameter will be ignored)
   * @param oids The oids to process
   */
  protected function loadNodes($oids) {
    $session = ObjectFactory::getInstance('session');

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
    while ($iterator->valid() && $counter < $request->getValue('nodes_per_call')) {
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

      $name = Message::get('Loading tree: continue with %0%', array($iterator->current()));
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
    $session = ObjectFactory::getInstance('session');

    // clear session variables
    $tmp = null;
    $session->set($this->REQUEST, $tmp);
    $session->set($this->REGISTRY, $tmp);
    $session->set($this->ITERATOR_ID, $tmp);
  }

  /**
   * Load the node with the given object id and assign it to the response.
   * @param oid The oid of the node to copy
   */
  protected function loadNode(ObjectId $oid) {
    // check if we already loaded the node
    if ($this->isRegistered($oid)) {
      return;
    }
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $session = ObjectFactory::getInstance('session');

    // restore the request from session
    $request = $session->get($this->REQUEST);

    // load the node
    $node = $persistenceFacade->load($oid);
    if ($node == null) {
      throw new PersistenceException("Can't load node '".$oid."'");
    }

    // translate all nodes to the requested language if requested
    if ($this->isLocalizedRequest()) {
      $localization = ObjectFactory::getInstance('localization');
      $localization->loadTranslation($node, $request->getValue('language'), true, true);
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

    if (Log::isInfoEnabled(__CLASS__)) {
      Log::info("Loaded: ".$node->getOID(), __CLASS__);
    }
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug($node->toString(), __CLASS__);
    }
  }

  /**
   * Register an object id in the registry
   * @param oid The object id to register
   */
  protected function register(ObjectId $oid) {
    $session = ObjectFactory::getInstance('session');
    $registry = $session->get($this->REGISTRY);
    $registry[] = $oid;
    $session->set($this->REGISTRY, $registry);
  }

  /**
   * Check if an object id is registered in the registry
   * @param oid The object id to check
   * @return Boolean whether the oid is registered or not
   */
  protected function isRegistered(ObjectId $oid) {
    $session = ObjectFactory::getInstance('session');
    $registry = $session->get($this->REGISTRY);

    return in_array($oid, $registry);
  }

  /**
   * Add a given node to the list variable of the response
   * @param node A reference to the node to add
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