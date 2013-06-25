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

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\config\ConfigurationException;
use wcmf\lib\model\Node;
use wcmf\lib\model\NodeUtil;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceAction;
use wcmf\lib\presentation\Controller;

/**
 * TreeController is used to visualize cms data in a tree view.
 *
 * <b>Input actions:</b>
 * - unspecified: Load the cild nodes of the given Node
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 *
 * @param[in] oid The object id of the parent Node whose children should be loaded (optional)
 * @param[in] sort The attribute to sort the children by (optional)
 * @param[out] list An array of associative arrays with keys 'oid', 'displayText', 'isFolder', 'hasChildren'
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class TreeController extends Controller {

  /**
   * @see Controller::executeKernel()
   */
  protected function executeKernel() {
    $request = $this->getRequest();
    $response = $this->getResponse();

    $oidStr = $request->getValue('oid');
    if ($oidStr == 'root') {
      // linkable types below root node
      $objects = $this->getLinkableTypes();
    }
    else {
      $oid = ObjectId::parse($oidStr);
      if ($oid != null) {
        // load children
        $objects = $this->getChildren($oid);
      }
    }

    // sort nodes if requested
    if ($request->hasValue('sort')) {
      $objects = Node::sort($objects, $request->getValue('sort'));
    }

    // create response
    $responseObjects = array();
    for ($i=0, $count=sizeof($objects); $i<$count; $i++) {
      $object = $objects[$i];
      if ($this->isVisible($object)) {
        $responseObjects[] = $this->getViewNode($object);
      }
    }
    $response->setValue('list', $responseObjects);

    // success
    $response->setAction('ok');
    return false;
  }

  /**
   * Get the children for a given oid.
   * @param oid The object id
   * @return Array of Node instances.
   */
  protected function getChildren($oid) {

    $permissionManager = ObjectFactory::getInstance('permissionManager');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    // check read permission on type
    $type = $oid->getType();
    if (!$permissionManager->authorize($type, '', PersistenceAction::READ)) {
      return array();
    }

    $objectsTmp = array();
    if ($this->isLinkableTypeNode($oid)) {
      // load instances of type
      $objectsTmp = $persistenceFacade->loadObjects($type, BuildDepth::SINGLE);
    }
    else {
      // load children of node
      if ($permissionManager->authorize($oid, '', PersistenceAction::READ)) {
        $node = $persistenceFacade->load($oid, 1);
        if ($node) {
          $objectsTmp = $node->getChildren();
        }
      }
    }

    // check read permission on instances
    $objects = array();
    foreach ($objectsTmp as $object) {
      if ($permissionManager->authorize($object->getOID(), '', PersistenceAction::READ)) {
        $objects[] = $object;
      }
    }

    return $objects;
  }

  /**
   * Get the oids of the root nodes.
   * @return An array of object ids.
   */
  protected function getRootOIDs() {
    // linkable types below root node
    return $this->getLinkableTypes();
  }

  /**
   * Get the view of a Node
   * @param node The Node to create the view for
   * @param displayText The text to display (will be taken from TreeController::getDisplayText() if not specified) [default: '']
   * @return An associative array whose keys correspond to Ext.tree.TreeNode config parameters
   */
  protected function getViewNode(Node $node, $displayText='') {
    if (strlen($displayText) == 0) {
      $displayText = trim($this->getDisplayText($node));
    }
    if (strlen($displayText) == 0) {
      $displayText = '-';
    }
    $oid = $node->getOID();
    $isFolder = ObjectId::isDummyId($oid->getFirstId());
    $hasChildren = $this->isLinkableTypeNode($oid) || sizeof($node->getNumChildren()) > 0;
    return array(
      'oid' => $node->getOID()->__toString(),
      'displayText' => $displayText,
      'isFolder' => $isFolder,
      'hasChildren' => $hasChildren
    );
  }

  /**
   * Test if a Node should be displayed in the tree
   * @param node Node to display
   * @return Boolean
   */
  protected function isVisible(Node $node) {
    return true;
  }

  /**
   * Get the display text for a Node
   * @param node Node to display
   * @return The display text.
   */
  protected function getDisplayText(Node $node) {
    if ($this->isLinkableTypeNode($node->getOID())) {
      return $node->getObjectDisplayName();
    }
    else {
      return strip_tags(preg_replace("/[\r\n']/", " ", NodeUtil::getDisplayValue($node)));
    }
  }

  /**
   * Get all linkable types
   * @return Array of Node instances
   */
  protected function getLinkableTypes() {
    // get linkable types from configuration
    $config = ObjectFactory::getConfigurationInstance();
    $appConfig = $config->getSection('application');
    if (isset($appConfig['linkableTypes']) && is_array($appConfig['linkableTypes'])) {
      $types = $appConfig['linkableTypes'];
    }
    else if (isset($appConfig['rootTypes']) && is_array($appConfig['rootTypes'])) {
      // try to get root types
      $types = $appConfig['rootTypes'];
    }
    else {
      throw new ConfigurationException("No root types defined.");
    }

    // filter types by read permission
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $nodes = array();
    foreach($types as $type) {
      if ($permissionManager->authorize($type, '', PersistenceAction::READ)) {
        $node = $persistenceFacade->create($type, BuildDepth::SINGLE);
        $nodes[] = $node;
      }
    }
    return $nodes;
  }

  /**
   * Check if the given oid belongs to a linkable type node
   * @param oid The object id
   * @return Boolean
   */
  protected function isLinkableTypeNode(ObjectId $oid) {
    if (ObjectId::isDummyId($oid->getFirstId())) {
      $type = $oid->getType();
      $linkableTypes = $this->getLinkableTypes();
      foreach ($linkableTypes as $linkableType) {
        if ($linkableType->getType() == $type) {
          return true;
        }
      }
    }
    return false;
  }
}
?>
