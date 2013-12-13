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
namespace wcmf\application\controller;

use wcmf\application\controller\BatchController;
use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\i18n\Message;
use wcmf\lib\model\Node;
use wcmf\lib\model\NodeUtil;
use wcmf\lib\model\PersistentIterator;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceException;
use wcmf\lib\persistence\PersistenceMapper;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\ApplicationException;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;

/**
 * CopyController is a controller that copies Nodes.
 *
 * <b>Input actions:</b>
 * - @em move Move the given Node and its children to the given target Node (delete original Node)
 * - @em copy Copy the given Node and its children to the given target Node (do not delete original Node)
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 *
 * @param[in] oid The oid of the Node to copy. The Node and all of its children will be copied
 * @param[in] targetoid The oid of the parent to attach the copy to (if it does not accept the
 *              Node type an error occurs) (optional, if empty the new Node has no parent)
 * @param[in] nodes_per_call The number of nodes to process in one call, default: 50
 * @param[in] target_initparams The name of the configuration section that holds the initparams for the target mappers.
 *              This allows to copy nodes to a different store, optional, does not work for move action
 * @param[in] recursive True/False wether to copy children too, only applies when copy action, default: true
 * @param[out] oid The object id of the newly created Node.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class CopyController extends BatchController {

  // session name constants
  private $REQUEST = 'CopyController.request';
  private $OBJECT_MAP = 'CopyController.objectmap';
  private $ITERATOR_ID = 'CopyController.iteratorid';

  private $_targetNode = null;
  private $_targetMapper = array();

  // default values, maybe overriden by corresponding request values (see above)
  private $_NODES_PER_CALL = 50;

  /**
   * @see Controller::initialize()
   */
  protected function initialize(Request $request, Response $response) {
    parent::initialize($request, $response);

    // initialize controller
    if ($request->getAction() != 'continue') {
      $session = ObjectFactory::getInstance('session');

      // set defaults
      if (!$request->hasValue('nodes_per_call')) {
        $request->setValue('nodes_per_call', $this->_NODES_PER_CALL);
      }
      if (!$request->hasValue('recursive')) {
        $request->setValue('recursive', true);
      }

      // store request in session
      $session->set($this->REQUEST, $request, array(WCMF_BASE."wcmf/lib/presentation/ControllerMessage.php"));
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
        $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
        $targetOID = $request->getValue('targetoid');
        $nodeOID = $request->getValue('oid');

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
          $template = &$possibleChildren[$nodeType];
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
    $name = '';
    if ($request->getAction() == 'move') {
      $name = Message::get('Moving');
    }
    else if ($request->getAction() == 'copy') {
      $name = Message::get('Copying');
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
   * @see LongTaskController::getDisplayText()
   */
  protected function getDisplayText($step) {
    return $this->_workPackages[$step-1]['name']." ...";
  }

  /**
   * Copy/Move the first node (oids parameter will be ignored)
   * @param oids The oids to process
   */
  protected function startProcess($oids) {
    $session = ObjectFactory::getInstance('session');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    // restore the request from session
    $request = $session->get($this->REQUEST);
    $action = $request->getAction();
    $targetOID = $request->getValue('targetoid');
    $nodeOID = $request->getValue('oid');

    // do the action
    if ($action == 'move') {
      if ($request->hasValue('target_initparams')) {
        $response = $this->getResponse();
        throw new ApplicationException($request, $response,
                ApplicationError::getGeneral("Moving nodes to a different store is not supported. Use the 'copy' action instead."));
      }

      // with move action, we only need to attach the Node to the new target
      // the children will not be loaded, they will be moved automatically
      $nodeCopy = $persistenceFacade->load($nodeOID, BuildDepth::SINGLE);
      if ($nodeCopy) {
        // attach the node to the target node
        $parentNode = $this->getTargetNode($targetOID);
        $parentNode->addNode($nodeCopy);

        // save changes
        $this->modify($nodeCopy);
        $nodeCopy->save();

        // set the result and finish
        $this->endProcess($nodeCopy->getOID());

        if (Log::isInfoEnabled(__CLASS__)) {
          Log::info("Moved: ".$nodeOID." to ".$parentNode->getOID(), __CLASS__);
        }
      }
    }
    else if ($action == 'copy') {
      // with copy action, we need to attach a copy of the Node to the new target,
      // the children need to be loaded and treated in the same way too
      $iterator = new PersistentIterator($nodeOID);
      $iteratorID = $iterator->save();
      $session->set($this->ITERATOR_ID, $iteratorID);

      // copy the first node in order to reduce the number of calls for a single copy
      $nodeCopy = $this->copyNode($iterator->current());
      if ($nodeCopy) {
        // attach the copy to the target node
        $parentNode = $this->getTargetNode($targetOID);
        $parentNode->addNode($nodeCopy);

        // save changes
        $this->modify($nodeCopy);
        $this->saveToTarget($nodeCopy);

        $iterator->next();

        // proceed if nodes are left
        if ($request->getBooleanValue('recursive') && $iterator->valid()) {
          $iteratorID = $iterator->save();
          $session->set($this->ITERATOR_ID, $iteratorID);

          $name = Message::get('Copying tree: continue with %0%', array($iterator->current()));
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
   * @param oids The oids to process
   */
  protected function copyNodes($oids) {
    $session = ObjectFactory::getInstance('session');

    // restore the request from session
    $request = $session->get($this->REQUEST);
    $action = $request->getAction();
    $targetOID = $request->getValue('targetoid');
    $nodeOID = $request->getValue('oid');

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
    while ($iterator->valid() && $counter < $request->getValue('nodes_per_call')) {
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

      $name = Message::get('Copying tree: continue with %0%', array($iterator->current()));
      $this->addWorkPackage($name, 1, array(null), 'copyNodes');
    }
    else {
      // set the result and finish
      $this->endProcess($this->getCopyOID($nodeOID));
    }
  }

  /**
   * Finish the process and set the result
   * @param oid The object id of the newly created Node
   */
  protected function endProcess(ObjectId $oid) {
    $this->_response->setValue('oid', $oid);

    $session = ObjectFactory::getInstance('session');

    // clear session variables
    $tmp = null;
    $session->set($this->REQUEST, $tmp);
    $session->set($this->OBJECT_MAP, $tmp);
    $session->set($this->ITERATOR_ID, $tmp);
  }

  /**
   * Create a copy of the node with the given object id. The returned
   * node is already persisted.
   * @param oid The oid of the node to copy
   * @return A reference to the copied node or null
   */
  protected function copyNode(ObjectId $oid) {
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug("Copying node ".$oid, __CLASS__);
    }
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    // load the original node
    $node = $persistenceFacade->load($oid, BUIDLDEPTH_SINGLE);
    if ($node == null) {
      throw new PersistenceException("Can't load node '".$oid."'");
    }

    // check if we already have a copy of the node
    $nodeCopy = $this->getCopy($node->getOID());
    if ($nodeCopy == null) {
      // if not, create a copy
      $nodeCopy = $persistenceFacade->create($node->getType(), BuildDepth::SINGLE);
      $node->copyValues($nodeCopy, false);
    }

    // create the connections to already copied parents
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug("Parents: ".join(', ', $node->getProperty('parentoids')), __CLASS__);
    }
    foreach ($node->getProperty('parentoids') as $parentOID) {
      $copiedParent = $this->getCopy($parentOID);
      if ($copiedParent != null) {
        $copiedParent->addNode($nodeCopy);
        if (Log::isDebugEnabled(__CLASS__)) {
          Log::debug("Added ".$nodeCopy->getOID()." to ".$copiedParent->getOID(), __CLASS__);
        }
      }
    }

    // save copy
    $this->saveToTarget($nodeCopy);
    $this->registerCopy($node, $nodeCopy);

    if (Log::isInfoEnabled(__CLASS__)) {
      Log::info("Copied: ".$node->getOID()." to ".$nodeCopy->getOID(), __CLASS__);
    }
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug($nodeCopy->toString(), __CLASS__);
    }

    // create the connections to already copied children
    // this must be done after saving the node in order to have a correct oid
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug("Children: ".join(', ', $node->getProperty('childoids')), __CLASS__);
    }
    foreach ($node->getProperty('childoids') as $childOID) {
      $copiedChild = $this->getCopy($childOID);
      if ($copiedChild != null) {
        $nodeCopy->addNode($copiedChild);
        $this->saveToTarget($copiedChild);
        if (Log::isDebugEnabled(__CLASS__)) {
          Log::debug("Added ".$copiedChild->getOID()." to ".$nodeCopy->getOID(), __CLASS__);
          Log::debug($copiedChild->toString(), __CLASS__);
        }
      }
    }

    return $nodeCopy;
  }

  /**
   * Get the target node from the request parameter targetoid
   * @param targetOID The oid of the target node or null
   * @return A reference to the node, an empty node, if targetoid is null
   */
  protected function getTargetNode(ObjectId $targetOID) {
    if ($this->_targetNode == null) {
      // load parent node or create an empty node if adding to root
      if ($targetOID == null) {
        $targetNode = new Node('');
      }
      else {
        $targetNode = $this->loadFromTarget($targetOID, BuildDepth::SINGLE);
      }
      $this->_targetNode = &$targetNode;
    }
    return $this->_targetNode;
  }

  /**
   * Register a copied node in the session for later reference
   * @param origNode A reference to the original node
   * @param copyNode A reference to the copied node
   */
  protected function registerCopy(PersistentObject $origNode, PersistentObject $copyNode) {
    $session = ObjectFactory::getInstance('session');
    $registry = $session->get($this->OBJECT_MAP);
    // store oid and corresponding base oid in the registry
    $registry[$origNode->getOID()] = $copyNode->getOID();
    $registry[$origNode->getBaseOID()] = $copyNode->getOID();
    $session->set($this->OBJECT_MAP, $registry);
  }

  /**
   * Get the object id of the copied node for a node id
   * @param oid The object id of the original node
   * @return The object id or null, if it does not exist already
   */
  protected function getCopyOID(ObjectId $origOID) {
    $session = ObjectFactory::getInstance('session');
    $registry = $session->get($this->OBJECT_MAP);

    $oid = $origOID;
    $requestedType = $oid->getType();

    // check if the oid exists in the registry
    if (!isset($registry[$origOID])) {
      // check if the corresponding base oid exists in the registry
      $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
      $origNodeType = $persistenceFacade->create($requestedType, BuildDepth::SINGLE);
      $baseOID = new ObjectId($origNodeType->getBaseType(), $oid->getId());
      if (!isset($registry[$baseOID])) {
        if (Log::isDebugEnabled(__CLASS__)) {
          Log::debug("Copy of ".$oid." not found.", __CLASS__);
        }
        return null;
      }
      else {
        $oid = $baseOID;
      }
    }

    $copyOID = $registry[$oid];

    // make sure to return the oid in the requested role
    if ($copyOID->getType() != $requestedType) {
      $copyOID = new ObjectId($requestedType, $copyOID->getId());
    }
    return $copyOID;
  }

  /**
   * Get the copied node for a node id
   * @param oid The object id of the original node
   * @return A reference to the copied node or null, if it does not exist already
   */
  protected function getCopy(ObjectId $origOID) {
    $copyOID = $this->getCopyOID($origOID);
    if ($copyOID != null) {
      $nodeCopy = $this->loadFromTarget($copyOID);
      return $nodeCopy;
    }
    else {
      return null;
    }
  }

  /**
   * Save a node to the target store
   * @param node A reference to the node
   */
  protected function saveToTarget(PersistentObject $node) {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $originalMapper = $persistenceFacade->getMapper($node->getType());

    $targetMapper = $this->getTargetMapper($originalMapper);
    $persistenceFacade->setMapper($node->getType(), $targetMapper);
    $node->save();

    $persistenceFacade->setMapper($node->getType(), $originalMapper);
  }

  /**
   * Load a node from the target store
   * @param oid The object id of the node
   * @return A reference to the node
   */
  protected function loadFromTarget(ObjectId $oid) {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $type = $oid->getType();
    $originalMapper = $persistenceFacade->getMapper($type);

    $targetMapper = $this->getTargetMapper($originalMapper);
    $persistenceFacade->setMapper($type, $targetMapper);
    $node = $persistenceFacade->load($oid, BuildDepth::SINGLE);

    $persistenceFacade->setMapper($node->getType(), $originalMapper);

    return $node;
  }

  /**
   * Get the target mapper for a source mapper (maybe saving to another store)
   * @param sourceMapper A reference to the source mapper
   * @param targetMapper A reference to the target mapper
   */
  protected function getTargetMapper(PersistenceMapper $sourceMapper) {
    // restore the request from session
    $session = ObjectFactory::getInstance('session');
    $request = $session->get($this->REQUEST);
    if ($request->hasValue('target_initparams')) {
      // get a mapper wih the target initparams
      $mapperClass = get_class($sourceMapper);
      if (!isset($this->_targetMapper[$mapperClass])) {
        $initSection = $request->getValue('target_initparams');
        $config = ObjectFactory::getConfigurationInstance();
        if (($initParams = $config->getSection($initSection)) === false) {
          throw new ConfigurationException("No '".$initSection."' section given in configfile.");
        }
        $targetMapper = new $mapperClass($initParams);
        $this->_targetMapper[$mapperClass] = &$targetMapper;
      }
      return $this->_targetMapper[$mapperClass];
    }
    else {
      return $sourceMapper;
    }
  }

  /**
   * Modify the given Node before save action (Called only for the copied root Node, not for its children)
   * @note subclasses will override this to implement special application requirements.
   * @param node A reference to the Node to modify.
   * @return True/False whether the Node was modified [default: false].
   */
  protected function modify(PersistentObject $node) {
    return false;
  }
}
?>

