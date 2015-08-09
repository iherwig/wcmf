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
use wcmf\lib\model\NodeUtil;
use wcmf\lib\model\PersistentIterator;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceException;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;

/**
 * CopyController is used to copy or move Node instances.
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ move </div>
 * <div>
 * Move the given Node and its children to the given target Node (delete original Node).
 * | Parameter             | Description
 * |-----------------------|-------------------------
 * | _in_ `oid`            | The object id of the Node to move. The Node and all of its children will be moved
 * | _in_ `targetOid`      | The object id of the parent to attach the moved Node to (if it does not accept the Node type an error occurs) (optional, if empty the new Node has no parent)
 * | _out_ `oid`           | The object id of the newly created Node
 * </div>
 * </div>
 *
 * <div class="controller-action">
 * <div> __Action__ copy </div>
 * <div>
 * Copy the given Node and its children to the given target Node (keep original Node).
 * | Parameter             | Description
 * |-----------------------|-------------------------
 * | _in_ `oid`            | The object id of the Node to move. The Node and all of its children will be moved
 * | _in_ `targetOid`      | The object id of the parent to attach the moved Node to (if it does not accept the Node type an error occurs) (optional, if empty the new Node has no parent)
 * | _in_ `nodesPerCall`   | The number of Node instances to copy in one call (default: 50)
 * | _in_ `recursive`      | Boolean whether to copy children too (default: _true_)
 * </div>
 * </div>
 *
 * For additional actions and parameters see [BatchController actions](@ref BatchController).
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class CopyController extends BatchController {

  // session name constants
  private $REQUEST = 'CopyController.request';
  private $OBJECT_MAP = 'CopyController.objectmap';
  private $ITERATOR_ID = 'CopyController.iteratorid';

  private $_targetNode = null;

  // default values, maybe overriden by corresponding request values (see above)
  private $_NODES_PER_CALL = 50;

  /**
   * @see Controller::initialize()
   */
  public function initialize(Request $request, Response $response) {
    parent::initialize($request, $response);

    // initialize controller
    if ($request->getAction() != 'continue') {
      $session = $this->getSession();

      // set defaults
      if (!$request->hasValue('nodesPerCall')) {
        $request->setValue('nodesPerCall', $this->_NODES_PER_CALL);
      }
      if (!$request->hasValue('recursive')) {
        $request->setValue('recursive', true);
      }

      // store request in session
      $session->set($this->REQUEST, $request);
      $map = array();
      $session->set($this->OBJECT_MAP, $map);
    }
  }

  /**
   * @see Controller::validate()
   */
  protected function validate() {
    $request = $this->getRequest();
    $response = $this->getResponse();
    if ($request->getAction() != 'continue') {
      // check request values
      $oid = ObjectId::parse($request->getValue('oid'));
      if(!$oid) {
        $response->addError(ApplicationError::get('OID_INVALID',
          array('invalidOids' => array($request->getValue('oid')))));
        return false;
      }
      if($request->hasValue('targetoid')) {
        $targetoid = ObjectId::parse($request->getValue('targetoid'));
        if(!$targetoid) {
          $response->addError(ApplicationError::get('OID_INVALID',
            array('invalidOids' => array($request->getValue('targetoid')))));
          return false;
        }
      }

      // check if the parent accepts this node type (only when not adding to root)
      $addOk = true;
      if ($request->hasValue('targetoid')) {
        $persistenceFacade = $this->getPersistenceFacade();
        $targetOID = ObjectId::parse($request->getValue('targetoid'));
        $nodeOID = ObjectId::parse($request->getValue('oid'));

        $targetNode = $this->getTargetNode($targetOID);
        $nodeType = $nodeOID->getType();
        $targetType = $targetOID->getType();

        $tplNode = $persistenceFacade->create($targetType, 1);
        $possibleChildren = NodeUtil::getPossibleChildren($targetNode, $tplNode);
        if (!in_array($nodeType, array_keys($possibleChildren))) {
          $response->addError(ApplicationError::get('ASSOCIATION_INVALID'));
          $addOk = false;
        }
        else {
          $template = $possibleChildren[$nodeType];
          if (!$template->getProperty('canCreate')) {
            $response->addError(ApplicationError::get('ASSOCIATION_INVALID'));
            $addOk = false;
          }
        }
      }
      if (!$addOk) {
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
    $request = $this->getRequest();
    $message = $this->getMessage();
    $name = '';
    if ($request->getAction() == 'move') {
      $name = $message->getText('Moving');
    }
    else if ($request->getAction() == 'copy') {
      $name = $message->getText('Copying');
    }
    $name .= ': '.$request->getValue('oid');

    if ($number == 0) {
      return array('name' => $name, 'size' => 1, 'oids' => array(1), 'callback' => 'startProcess');
    }
    else {
      return null;
    }
  }

  /**
   * Copy/Move the first node (oids parameter will be ignored)
   * @param $oids The oids to process
   */
  protected function startProcess($oids) {
    $session = $this->getSession();

    // restore the request from session
    $request = $session->get($this->REQUEST);
    $action = $request->getAction();
    $targetOID = ObjectId::parse($request->getValue('targetoid'));
    $nodeOID = ObjectId::parse($request->getValue('oid'));

    // do the action
    if ($action == 'move') {
      $persistenceFacade = $this->getPersistenceFacade();
      $transaction = $persistenceFacade->getTransaction();
      $transaction->begin();
      // with move action, we only need to attach the Node to the new target
      // the children will not be loaded, they will be moved automatically
      $nodeCopy = $persistenceFacade->load($nodeOID);
      if ($nodeCopy) {
        if ($targetOID != null) {
          // attach the node to the target node
          $parentNode = $this->getTargetNode($targetOID);
          $parentNode->addNode($nodeCopy);
        }

        $this->modify($nodeCopy);

        // set the result and finish
        $this->endProcess($nodeCopy->getOID());

        $logger = $this->getLogger();
        if ($logger->isInfoEnabled()) {
          $logger->info("Moved: ".$nodeOID." to ".$parentNode->getOID());
        }
      }
      $transaction->commit();
    }
    else if ($action == 'copy') {
      // with copy action, we need to attach a copy of the Node to the new target,
      // the children need to be loaded and treated in the same way too
      $iterator = new PersistentIterator($nodeOID, array('composite'));
      $iteratorID = $iterator->save();
      $session->set($this->ITERATOR_ID, $iteratorID);

      // copy the first node in order to reduce the number of calls for a single copy
      $nodeCopy = $this->copyNode($iterator->current());
      if ($nodeCopy) {
        if ($targetOID != null) {
          // attach the copy to the target node
          $parentNode = $this->getTargetNode($targetOID);
          $parentNode->addNode($nodeCopy);
        }

        $this->modify($nodeCopy);

        $iterator->next();

        // proceed if nodes are left
        if ($request->getBooleanValue('recursive') && $iterator->valid()) {
          $iteratorID = $iterator->save();
          $session->set($this->ITERATOR_ID, $iteratorID);

          $name = $this->getMessage()->getText('Copying tree: continue with %0%',
                  array($iterator->current()));
          $this->addWorkPackage($name, 1, array(null), 'copyNodes');
        }
        else {
          // set the result and finish
          $this->endProcess($nodeCopy->getOID());
        }
      }
    }
  }

  /**
   * Copy nodes provided by the persisted iterator (oids parameter will be ignored)
   * @param $oids The oids to process
   */
  protected function copyNodes($oids) {
    $session = $this->getSession();

    // restore the request from session
    $request = $session->get($this->REQUEST);
    $action = $request->getAction();
    $targetOID = ObjectId::parse($request->getValue('targetoid'));
    $nodeOID = ObjectId::parse($request->getValue('oid'));

    // check for iterator in session
    $iterator = null;
    $iteratorID = $session->get($this->ITERATOR_ID);
    if ($iteratorID != null) {
      $iterator = PersistentIterator::load($iteratorID);
    }

    // no iterator, finish
    if ($iterator == null) {
      // set the result and finish
      $this->endProcess($this->getCopyOID($nodeOID));
    }

    // process _NODES_PER_CALL nodes
    $counter = 0;
    while ($iterator->valid() && $counter < $request->getValue('nodesPerCall')) {
      $currentOID = $iterator->current();
      $this->copyNode($currentOID);

      $iterator->next();
      $counter++;
    }

    // decide what to do next
    if ($iterator->valid()) {
      // proceed with current iterator
      $iteratorID = $iterator->save();
      $session->set($this->ITERATOR_ID, $iteratorID);

      $name = $this->getMessage()->getText('Copying tree: continue with %0%',
              array($iterator->current()));
      $this->addWorkPackage($name, 1, array(null), 'copyNodes');
    }
    else {
      // set the result and finish
      $this->endProcess($this->getCopyOID($nodeOID));
    }
  }

  /**
   * Finish the process and set the result
   * @param $oid The object id of the newly created Node
   */
  protected function endProcess(ObjectId $oid) {
    $response = $this->getResponse();
    $response->setValue('oid', $oid);

    $session = $this->getSession();

    // clear session variables
    $tmp = null;
    $session->set($this->REQUEST, $tmp);
    $session->set($this->OBJECT_MAP, $tmp);
    $session->set($this->ITERATOR_ID, $tmp);
  }

  /**
   * Create a copy of the node with the given object id. The returned
   * node is already persisted.
   * @param $oid The oid of the node to copy
   * @return The copied Node or null
   */
  protected function copyNode(ObjectId $oid) {
    $logger = $this->getLogger();
    if ($logger->isDebugEnabled()) {
      $logger->debug("Copying node ".$oid);
    }
    $persistenceFacade = $this->getPersistenceFacade();
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();

    // load the original node
    $node = $persistenceFacade->load($oid);
    if ($node == null) {
      throw new PersistenceException("Can't load node '".$oid."'");
    }

    // check if we already have a copy of the node
    $nodeCopy = $this->getCopy($node->getOID());
    if ($nodeCopy == null) {
      // if not, create it
      $nodeCopy = $persistenceFacade->create($node->getType());
      $node->copyValues($nodeCopy, false);
    }

    // save copy
    $this->registerCopy($node, $nodeCopy);

    if ($logger->isInfoEnabled()) {
      $logger->info("Copied: ".$node->getOID()." to ".$nodeCopy->getOID());
    }
    if ($logger->isDebugEnabled()) {
      $logger->debug($nodeCopy->__toString());
    }

    // create the connections to already copied relatives
    // this must be done after saving the node in order to have a correct oid
    $mapper = $node->getMapper();
    $relations = $mapper->getRelations();
    foreach ($relations as $relation) {
      if ($relation->getOtherNavigability()) {
        $otherRole = $relation->getOtherRole();
        $relativeValue = $node->getValue($otherRole);
        $relatives = $relation->isMultiValued() ? $relativeValue :
                ($relativeValue != null ? array($relativeValue) : array());
        foreach ($relatives as $relative) {
          $copiedRelative = $this->getCopy($relative->getOID());
          if ($copiedRelative != null) {
            $nodeCopy->addNode($copiedRelative, $otherRole);
            if ($logger->isDebugEnabled()) {
              $logger->debug("Added ".$copiedRelative->getOID()." to ".$nodeCopy->getOID());
              $logger->debug($copiedRelative->__toString());
            }
          }
        }
      }
    }
    $changedOids = $transaction->commit();
    $this->updateCopyOIDs($changedOids);

    return $nodeCopy;
  }

  /**
   * Get the target node from the request parameter targetoid
   * @param $targetOID The oid of the target node
   * @return Node instance
   */
  protected function getTargetNode(ObjectId $targetOID) {
    if ($this->_targetNode == null) {
      // load parent node
      $persistenceFacade = $this->getPersistenceFacade();
      $targetNode = $persistenceFacade->load($targetOID);
      $this->_targetNode = $targetNode;
    }
    return $this->_targetNode;
  }

  /**
   * Register a copied node in the session for later reference
   * @param $origNode A reference to the original node
   * @param $copyNode A reference to the copied node
   */
  protected function registerCopy(PersistentObject $origNode, PersistentObject $copyNode) {
    $session = $this->getSession();
    $registry = $session->get($this->OBJECT_MAP);
    // store oid in the registry
    $registry[$origNode->getOID()->__toString()] = $copyNode->getOID()->__toString();
    $session->set($this->OBJECT_MAP, $registry);
  }

  /**
   * Update the copied oids in the registry
   * @param $oidMap Map of changed oids (key: old value, value: new value)
   */
  protected function updateCopyOIDs(array $oidMap) {
    $session = $this->getSession();
    $registry = $session->get($this->OBJECT_MAP);
    // registry maybe deleted already if it's the last step
    if ($registry) {
      $flippedRegistry = array_flip($registry);
      foreach ($oidMap as $oldOid => $newOid) {
        if (isset($flippedRegistry[$oldOid])) {
          $key = $flippedRegistry[$oldOid];
          unset($flippedRegistry[$oldOid]);
          $flippedRegistry[$newOid] = $key;
        }
      }
      $registry = array_flip($flippedRegistry);
      $session->set($this->OBJECT_MAP, $registry);
    }
  }

  /**
   * Get the object id of the copied node for a node id
   * @param $origOID The object id of the original node
   * @return ObjectId or null, if it does not exist already
   */
  protected function getCopyOID(ObjectId $origOID) {
    $session = $this->getSession();
    $registry = $session->get($this->OBJECT_MAP);

    // check if the oid exists in the registry
    $oidStr = $origOID->__toString();
    if (!isset($registry[$oidStr])) {
      $logger = $this->getLogger();
      if ($logger->isDebugEnabled()) {
        $logger->debug("Copy of ".$oidStr." not found.");
      }
      return null;
    }

    $copyOID = ObjectId::parse($registry[$oidStr]);
    return $copyOID;
  }

  /**
   * Get the copied node for a node id
   * @param $origOID The object id of the original node
   * @return Copied Node or null, if it does not exist already
   */
  protected function getCopy(ObjectId $origOID) {
    $copyOID = $this->getCopyOID($origOID);
    if ($copyOID != null) {
      $persistenceFacade = $this->getPersistenceFacade();
      $nodeCopy = $persistenceFacade->load($copyOID);
      return $nodeCopy;
    }
    else {
      return null;
    }
  }

  /**
   * Modify the given Node before save action (Called only for the copied root Node, not for its children)
   * @note Subclasses will override this to implement special application requirements.
   * @param $node A reference to the Node to modify.
   * @return Boolean whether the Node was modified (default: false)
   */
  protected function modify(PersistentObject $node) {
    return false;
  }
}
?>