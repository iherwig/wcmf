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
require_once(BASE."wcmf/application/controller/class.BatchController.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(BASE."wcmf/lib/model/class.PersistentIterator.php");
require_once(BASE."wcmf/lib/model/class.Node.php");
require_once(BASE."wcmf/lib/model/class.NodeUtil.php");

/**
 * @class CopyController
 * @ingroup Controller
 * @brief CopyController is a controller that copies Nodes.
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
class CopyController extends BatchController
{
  // session name constants
  var $REQUEST = 'CopyController.request';
  var $OBJECT_MAP = 'CopyController.objectmap';
  var $ITERATOR_ID = 'CopyController.iteratorid';

  var $_targetNode = null;
  var $_targetMapper = array();

  // default values, maybe overriden by corresponding request values (see above)
  var $_NODES_PER_CALL = 50;

  /**
   * @see Controller::initialize()
   */
  function initialize(&$request, &$response)
  {
    parent::initialize($request, $response);

    // initialize controller
    if ($request->getAction() != 'continue')
    {
      $session = &SessionData::getInstance();

      // set defaults
      if (!$request->hasValue('nodes_per_call')) {
        $request->setValue('nodes_per_call', $this->_NODES_PER_CALL);
      }
      if (!$request->hasValue('recursive')) {
        $request->setValue('recursive', true);
      }

      // store request in session
      $session->set($this->REQUEST, $request, array(BASE."wcmf/lib/presentation/class.ControllerMessage.php"));
      $map = array();
      $session->set($this->OBJECT_MAP, $map);
    }
  }
  /**
   * @see Controller::validate()
   */
  function validate()
  {
    if ($this->_request->getAction() != 'continue')
    {
      // check request values
      if(strlen($this->_request->getValue('oid')) == 0)
      {
        $this->appendErrorMsg("No 'oid' given in data.");
        return false;
      }
      if($this->_request->hasValue('targetoid') &&
        !PersistenceFacade::isValidOID($this->_request->getValue('targetoid')))
      {
        $this->appendErrorMsg("Invalid 'targetoid' parameter given in data.");
        return false;
      }

      // check if the parent accepts this node type (only when not adding to root)
      $addOk = true;
      if ($this->_request->hasValue('targetoid'))
      {
        $persistenceFacade = &PersistenceFacade::getInstance();
        $targetOID = $this->_request->getValue('targetoid');
        $nodeOID = $this->_request->getValue('oid');

        $targetNode = $this->getTargetNode($targetOID);
        $nodeType = PersistenceFacade::getOIDParameter($nodeOID, 'type');
        $targetType = PersistenceFacade::getOIDParameter($targetOID, 'type');

        $tplNode = &$persistenceFacade->create($targetType, 1);
        $possibleChildren = NodeUtil::getPossibleChildren($targetNode, $tplNode);
        if (!in_array($nodeType, array_keys($possibleChildren)))
        {
          $this->appendErrorMsg(Message::get("%1% does not accept children of type %2%. The parent type is not compatible.",
              array($targetOID, $nodeType)));
          $addOk = false;
        }
        else
        {
          $template = &$possibleChildren[$nodeType];
          if (!$template->getProperty('canCreate'))
          {
            $this->appendErrorMsg(Message::get("%1% does not accept children of type %2%. The maximum number of children of that type is reached.",
                array($targetOID, $nodeType)));
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
  function getWorkPackage($number)
  {
    $name = '';
    if ($this->_request->getAction() == 'move') {
      $name = Message::get('Moving');
    }
    else if ($this->_request->getAction() == 'copy') {
      $name = Message::get('Copying');
    }
    $name .= ': '.$this->_request->getValue('oid');

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
  function getDisplayText($step)
  {
    return $this->_workPackages[$step-1]['name']." ...";
  }
  /**
   * Copy/Move the first node (oids parameter will be ignored)
   * @param oids The oids to process
   */
  function startProcess($oids)
  {
    $session = &SessionData::getInstance();

    // restore the request from session
    $request = $session->get($this->REQUEST);
    $action = $request->getAction();
    $targetOID = $request->getValue('targetoid');
    $nodeOID = $request->getValue('oid');

    // do the action
    if ($action == 'move')
    {
      if ($request->hasValue('target_initparams')) {
        WCMFException::throwEx("Moving nodes to a different store is not supported. Use the 'copy' action instead.", __FILE__, __LINE__);
      }

      // with move action, we only need to attach the Node to the new target
      // the children will not be loaded, they will be moved automatically
      $nodeCopy = &$persistenceFacade->load($nodeOID, BUILDDEPTH_SINGLE);
      if ($nodeCopy)
      {
        // attach the node to the target node
        $parentNode = &$this->getTargetNode($targetOID);
        $parentNode->addChild($nodeCopy);

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
    else if ($action == 'copy')
    {
      // with copy action, we need to attach a copy of the Node to the new target,
      // the children need to be loaded and treated in the same way too
      $iterator = new PersistentIterator($nodeOID);
      $iteratorID = $iterator->save();
      $session->set($this->ITERATOR_ID, $iteratorID);

      // copy the first node in order to reduce the number of calls for a single copy
      $nodeCopy = &$this->copyNode($iterator->getCurrentOID());
      if ($nodeCopy)
      {
        // attach the copy to the target node
        $parentNode = &$this->getTargetNode($targetOID);
        $parentNode->addChild($nodeCopy);

        // save changes
        $this->modify($nodeCopy);
        $this->saveToTarget($nodeCopy);

        $iterator->proceed();

        // proceed if nodes are left
        if ($request->getBooleanValue('recursive') && !$iterator->isEnd())
        {
          $iteratorID = $iterator->save();
          $session->set($this->ITERATOR_ID, $iteratorID);

          $name = Message::get('Copying tree: continue with %1%', array($iterator->getCurrentOID()));
          $this->addWorkPackage($name, 1, array(null), 'copyNodes');
        }
        else
        {
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
  function copyNodes($oids)
  {
    $session = &SessionData::getInstance();

    // restore the request from session
    $request = $session->get($this->REQUEST);
    $action = $request->getAction();
    $targetOID = $request->getValue('targetoid');
    $nodeOID = $request->getValue('oid');

    // check for iterator in session
    $iterator = null;
    $iteratorID = $session->get($this->ITERATOR_ID);
    if ($iteratorID != null) {
      $iterator = &PersistentIterator::load($iteratorID);
    }

    // no iterator, finish
    if ($iterator == null)
    {
      // set the result and finish
      $this->endProcess($this->getCopyOID($nodeOID));
    }

    // process _NODES_PER_CALL nodes
    $counter = 0;
    while (!$iterator->isEnd() && $counter < $request->getValue('nodes_per_call'))
    {
      $currentOID = $iterator->getCurrentOID();
      $this->copyNode($currentOID);

      $iterator->proceed();
      $counter++;
    }

    // decide what to do next
    if (!$iterator->isEnd())
    {
      // proceed with current iterator
      $iteratorID = $iterator->save();
      $session->set($this->ITERATOR_ID, $iteratorID);

      $name = Message::get('Copying tree: continue with %1%', array($iterator->getCurrentOID()));
      $this->addWorkPackage($name, 1, array(null), 'copyNodes');
    }
    else
    {
      // set the result and finish
      $this->endProcess($this->getCopyOID($nodeOID));
    }
  }
  /**
   * Finish the process and set the result
   * @param oid The object id of the newly created Node
   */
  function endProcess($oid)
  {
    $this->_response->setValue('oid', $oid);

    $session = &SessionData::getInstance();

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
  function &copyNode($oid)
  {
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug("Copying node ".$oid, __CLASS__);
    }
    $persistenceFacade = &PersistenceFacade::getInstance();

    // load the original node
    $node = &$persistenceFacade->load($oid, BUIDLDEPTH_SINGLE);
    if ($node == null) {
      WCMFException::throwEx("Can't load node '".$oid."'", __FILE__, __LINE__);
    }

    // check if we already have a copy of the node
    $nodeCopy = &$this->getCopy($node->getOID());
    if ($nodeCopy == null)
    {
      // if not, create a copy
      $nodeCopy = &$persistenceFacade->create($node->getType(), BUILDDEPTH_SINGLE);
      $node->copyValues($nodeCopy, false);
    }

    // create the connections to already copied parents
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug("Parents: ".join(', ', $node->getProperty('parentoids')), __CLASS__);
    }
    foreach ($node->getProperty('parentoids') as $parentOID)
    {
      $copiedParent = &$this->getCopy($parentOID);
      if ($copiedParent != null) {
        $copiedParent->addChild($nodeCopy);
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
    foreach ($node->getProperty('childoids') as $childOID)
    {
      $copiedChild = &$this->getCopy($childOID);
      if ($copiedChild != null) {
        $nodeCopy->addChild($copiedChild);
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
  function &getTargetNode($targetOID)
  {
    if ($this->_targetNode == null)
    {
      // load parent node or create an empty node if adding to root
      if ($targetOID == null) {
        $targetNode = new Node('');
      }
      else {
        $targetNode = &$this->loadFromTarget($targetOID, BUILDDEPTH_SINGLE);
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
  function registerCopy(&$origNode, &$copyNode)
  {
    $session = &SessionData::getInstance();
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
  function getCopyOID($origOID)
  {
    $session = &SessionData::getInstance();
    $registry = $session->get($this->OBJECT_MAP);

    $oid = $origOID;
    $origOIDParts = PersistenceFacade::decomposeOID($oid);
    $requestedType = $origOIDParts['type'];

    // check if the oid exists in the registry
    if (!isset($registry[$origOID])) {
      // check if the corresponding base oid exists in the registry
      $persistenceFacade = &PersistenceFacade::getInstance();
      $origNodeType = &$persistenceFacade->create($requestedType, BUILDDEPTH_SINGLE);
      $baseOID = PersistenceFacade::composeOID(array('type' => $origNodeType->getBaseType(), 'id' => $origOIDParts['id']));
      if (!isset($registry[$baseOID]))
      {
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
    $copyOIDParts = PersistenceFacade::decomposeOID($copyOID);
    if ($copyOIDParts['type'] != $requestedType) {
      $copyOID = PersistenceFacade::composeOID(array('type' => $requestedType, 'id' => $copyOIDParts['id']));
    }
    return $copyOID;
  }
  /**
   * Get the copied node for a node id
   * @param oid The object id of the original node
   * @return A reference to the copied node or null, if it does not exist already
   */
  function &getCopy($origOID)
  {
    $copyOID = $this->getCopyOID($origOID);
    if ($copyOID != null)
    {
      $nodeCopy = &$this->loadFromTarget($copyOID);
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
  function saveToTarget(&$node)
  {
    $persistenceFacade = &PersistenceFacade::getInstance();
    $originalMapper = &$persistenceFacade->getMapper($node->getType());

    $targetMapper = &$this->getTargetMapper($originalMapper);
    $persistenceFacade->setMapper($node->getType(), $targetMapper);
    $node->save();

    $persistenceFacade->setMapper($node->getType(), $originalMapper);
  }
  /**
   * Load a node from the target store
   * @param oid The object id of the node
   * @return A reference to the node
   */
  function &loadFromTarget($oid)
  {
    $persistenceFacade = &PersistenceFacade::getInstance();
    $type = PersistenceFacade::getOIDParameter($oid, 'type');
    $originalMapper = &$persistenceFacade->getMapper($type);

    $targetMapper = &$this->getTargetMapper($originalMapper);
    $persistenceFacade->setMapper($type, $targetMapper);
    $node = &$persistenceFacade->load($oid, BUILDDEPTH_SINGLE);

    $persistenceFacade->setMapper($node->getType(), $originalMapper);

    return $node;
  }
  /**
   * Get the target mapper for a source mapper (maybe saving to another store)
   * @param sourceMapper A reference to the source mapper
   * @param targetMapper A reference to the target mapper
   */
  function &getTargetMapper(&$sourceMapper)
  {
    // restore the request from session
    $session = &SessionData::getInstance();
    $request = $session->get($this->REQUEST);
    if ($request->hasValue('target_initparams'))
    {
      // get a mapper wih the target initparams
      $mapperClass = get_class($sourceMapper);
      if (!isset($this->_targetMapper[$mapperClass]))
      {
        $initSection = $request->getValue('target_initparams');
        $parser = &InifileParser::getInstance();
        if (($initParams = $parser->getSection($initSection)) === false)
        {
          WCMFException::throwEx("No '".$initSection."' section given in configfile.", __FILE__, __LINE__);
          return null;
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
  function modify(&$node)
  {
    return false;
  }
}
?>

