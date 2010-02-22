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
require_once(BASE."wcmf/lib/util/class.Log.php");
require_once(BASE."wcmf/lib/util/class.StringUtil.php");
require_once(BASE."wcmf/lib/util/class.FormUtil.php");
require_once(BASE."wcmf/lib/model/class.Node.php");
require_once(BASE."wcmf/lib/model/class.NodeIterator.php");
require_once(BASE."wcmf/lib/model/class.NodeProcessor.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceFacade.php");

/**
 * Globals .
 */
// create only one instance of ValueRenderer
$gValueRenderer = null;

/**
 * @class NodeUtil
 * @ingroup Model
 * @brief NodeUtil provides services for the Node class. All methods are static.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NodeUtil
{
  /**
   * Get the path to a given Node (from a root node).
   * @param node The Node to find the path for
   * @return An array containing the nodes in the path
   */
  public static function getPath($node)
  {
    $path = array();
    $persistenceFacade = PersistenceFacade::getInstance();
    $parents = $node->getParents(false);
    if (sizeof($parents) > 0)
    {
      $parentOID = $parents[0]->getOID();
      while (ObjectId::isValid($parentOID))
      {
        $nodeInPath = $persistenceFacade->load($parentOID, BUILDDEPTH_SINGLE);
        if ($nodeInPath)
        {
          array_push($path, $nodeInPath);
          $parents = $nodeInPath->getParents(false);
          if (sizeof($parents) > 0) {
            $parentOID = $parents[0]->getOID();
            continue;
          }
        }
        break;
      }
    }
    return $path;
  }
  /**
   * Get the relations that connect a type to another type.
   * @param type The type to start from
   * @param otherType The type to connect to
   * @param hierarchyType The hierarchy type that the other type has in relation to this type
   *                      'parent', 'child', 'undefined' or 'all' to get all relations [default: 'all']
   * @param relations Internal use only
   * @return An array of RelationDescription instances, empty if no connection exists
   */
  public static function getConnection($type, $otherType, $hierarchyType, $relations=null)
  {
    if ($relations == null) {
      $relations = array();
    }
    $persistenceFacade = PersistenceFacade::getInstance();
    $mapper = $persistenceFacade->getMapper($type);
    $relationDescs = $mapper->getRelations($hierarchyType);
    if (sizeof($relationDescs) > 0)
    {
      // check relations
      foreach ($relationDescs as $relationDesc)
      {
        // prevent recursion
        if ($type == $otherType || $relationDesc->otherType != $type)
        {
          if ($relationDesc->otherType == $otherType) {
            // found -> return
            $relations[] = $relationDesc;
            return $relations;
          }
          else {
            // nothing found -> proceed with next generation
            $nextRelations = $relations;
            $nextRelations[] = $relationDesc;
            $result = NodeUtil::getConnection($relationDesc->otherType, $otherType, $hierarchyType, $nextRelations);
            if (sizeof($result) > 0) {
              return $result;
            }
          }
        }
      }
    }
    return array();
  }
  /**
   * Get the query used to select all Nodes of a type.
   * @param nodeType The Node type
   * @return The serialized query string to be used with ObjectQuery::executeString.
   */
  function getNodeQuery($nodeType)
  {
    $query = PersistenceFacade::createObjectQuery($nodeType);
    return $query->toString();
  }
  /**
   * Get the query used to select a special Node.
   * @param nodeType The Node type
   * @param oid The object id of the node
   * @return The serialized query string to be used with ObjectQuery::executeString.
   */
  function getSelfQuery($nodeType, $oid)
  {
    $persistenceFacade = PersistenceFacade::getInstance();
    $query = PersistenceFacade::createObjectQuery($nodeType);
    $tpl = $query->getObjectTemplate($nodeType);
    $mapper = &$tpl->getMapper();
    $oidParts = PersistenceFacade::decomposeOID($oid);
    $i = 0;
    foreach ($mapper->getPkNames() as $pkName) {
      $tpl->setValue($pkName, '= '.$oidParts['id'][$i++]);
    }
    return $query->toString();
  }
  /**
   * Get the query used to select all parent Nodes of a given role.
   * @param parentRole The parent role
   * @param childNode The Node to select the parents for
   * @return The serialized query string to be used with ObjectQuery::executeString.
   */
  function getParentQuery($parentRole, $childNode)
  {
    $parentType = $childNode->getTypeForRole($parentRole);
  //Log::error($parentRole." ".$parentType." ".$childNode->toString(), __CLASS__);
    $query = PersistenceFacade::createObjectQuery($parentType);
    $tpl = $query->getObjectTemplate($parentType);
    // prepare the child: use a new one and set the primary key values
    $cTpl = $query->getObjectTemplate($childNode->getType());
    $mapper = &$childNode->getMapper();
    foreach ($mapper->getPkNames() as $pkName) {
      $cTpl->setValue($pkName, '= '.$childNode->getValue($pkName));
    }
    $tpl->addChild($cTpl, $childNode->getOppositeRole($parentRole));
  //Log::error($query->toString(), __CLASS__);
    return $query->toString();
  }
  /**
   * Get the query used to select all child Nodes of a given role.
   * @param parentNode The Node to select the children for
   * @param childRole The child role
   * @return The serialized query string to be used with ObjectQuery::executeString.
   */
  function getChildQuery($parentNode, $childRole)
  {
    $childType = $parentNode->getTypeForRole($childRole);
  //Log::error($childRole." ".$childType." ".$parentNode->toString(), __CLASS__);
    $query = PersistenceFacade::createObjectQuery($childType);
    $tpl = $query->getObjectTemplate($childType);
    // prepare the parent: use a new one and set the primary key values
    $pTpl = $query->getObjectTemplate($parentNode->getType());
    $mapper = $parentNode->getMapper();
    foreach ($mapper->getPkNames() as $pkName) {
      $pTpl->setValue($pkName, '= '.$parentNode->getValue($pkName));
    }
    $pTpl->addChild($tpl);
  //Log::error($query->toString(), __CLASS__);
    return $query->toString();
  }
  /**
   * Get roles of allowed parents for a Node by comparing the existing parents of realNode with the possible parents of tplNode.
   * @param realNode A reference to the Node that defines the existing parents (property parentoids must be given)
   * @param tplNode A reference to the Node that defines the possible parents (property possibleParents must be given)
   * @return An associative array with the parent role as key and a template of the parent as value (with the correct role in realtion to realNode).
   * @note: The template has the following extra properties (use Node::getProperty()):
   *        - assignedParent gives the parent object if an instance already assigned to that parent role
   *          (per definition only one instance is assignable to a parent role)
   *        - canAssociate (boolean) indicating if an instance may be associated (depends on the navigability from the parent)
   *        - composition (boolean) indicating if the parent child relation is a composition
   */
  function getPossibleParents(&$realNode, &$tplNode)
  {
    $allowedParentRoles = array();
    if ($tplNode != null && $realNode != null)
    {
      $persistenceFacade = PersistenceFacade::getInstance();
      $possibleParents = $tplNode->getPossibleParents();
      foreach ($possibleParents as $type => $roles)
      {
        foreach ($roles as $role)
        {
          $possibleParent = &$persistenceFacade->create($type, 1);

          // check if the parent itself knows the role as children and if the relation is a composition
          $child = $possibleParent->getFirstChild(null, $role, null, null);
          if ($child != null)
          {
            $possibleParent->setProperty('canAssociate', true);
            $possibleParent->setProperty('composition', $child->getProperty('composition'));
          }
          else
          {
            $possibleParent->setProperty('canAssociate', false);
            $possibleParent->setProperty('composition', true);
          }

          // get the already assigned parent, if existing (we expect only one)
          foreach ($realNode->getParentOIDsByRole($role) as $parentOID)
          {
            // make parent a reference instead of pass &$parent
            // (this avoids call-time pass-by-reference warning)
            $ref = &$parent;
            $parent = &$persistenceFacade->load($parentOID, BUILDDEPTH_SINGLE);
            $possibleParent->setProperty('assignedParent', $parent);
            break;
          }

          // set the role in realation to realNode
          $possibleParent->setRole($realNode->getOID(), $role);
          $allowedParentRoles[$role] = &$possibleParent;
        }
      }
    }
    return $allowedParentRoles;
  }
  /**
   * Get allowed child types for a Node by comparing the existing children of realNode with the possible children of tplNode.
   * @param realNode A reference to the Node that defines the existing children (property childoids must be given)
   * @param tplNode A reference to the Node that defines the possible children (built with depth = 1, property possibleChildren must be given)
   * @param resolveManyToMany True/False wether for all many to many children the real subject types should be returned [default: true]
   * @return An associative array with the child role as key and a template of the child as values (with the correct role in realtion to realNode).
   * @note: The template has the following extra properties (use Node::getProperty()):
   *        - canCreate (boolean) indicating if an instance may be created (depends on the multiplicity)
   *        - realSubject the type of the real subject if the template is acting as proxy (many to many instance)
   */
  function getPossibleChildren(&$realNode, &$tplNode, $resolveManyToMany=true)
  {
    $allowedChildRoles = array();
    if ($tplNode != null && $realNode != null)
    {
      $persistenceFacade = PersistenceFacade::getInstance();
      $possibleChildren = $tplNode->getChildren();
      for ($i=0; $i<sizeof($possibleChildren); $i++)
      {
        $possibleChild = &$possibleChildren[$i];
        $role = $possibleChild->getRole($tplNode->getOID());

        // get the number of existing children
        $occurs = sizeof($realNode->getChildOIDsByRole($role));
        if ($possibleChild->getProperty('maxOccurs') == 'unbounded' || ($occurs < $possibleChild->getProperty('maxOccurs'))) {
           $possibleChild->setProperty('canCreate', true);
        }
        else {
           $possibleChild->setProperty('canCreate', false);
        }
        // check if we have an association object
        // if yes we set the composition property to false (instances are deleted with the parent)
        // and get the display name from the associated type
        $realSubjectType = NodeUtil::getRealSubjectType($possibleChild, $realNode->getType());
        if ($realSubjectType != null)
        {
          $associatedNode = &$persistenceFacade->create($realSubjectType, BUILDTYPE_SINGLE);
          $possibleChild->setProperty('composition', false);
          $possibleChild->setProperty('realSubject', $associatedNode);
        }

        if ($realSubjectType != null && $resolveManyToMany) {
          $role = $realSubjectType;
        }

        // set the role in realation to realNode
        $possibleChild->setRole($realNode->getOID(), $role);
        $allowedChildRoles[$role] = &$possibleChild;
      }
    }
    return $allowedChildRoles;
  }
  /**
   * Get the real subject type for a proxy node, that is a many to many instance. A many to many instance
   * serves as proxy between a client and a real subject, where the client is the parent node in this case
   * and the proxy is the child node.
   * @param proxy The (many to many) proxy node
   * @param parentType The parent type
   * @return The type
   */
  function getRealSubjectType(&$proxy, $parentType)
  {
    $manyToMany = $proxy->getProperty('manyToMany');
    if (is_array($manyToMany) && sizeof($manyToMany) == 2)
    {
      // get the type of the real subject from the manyToMany property
      foreach($proxy->getProperty('manyToMany') as $curParentType)
      {
        if ($curParentType != $parentType) {
          return $curParentType;
        }
      }
    }
    return null;
  }
  /**
   * Get the display value names of a Node.
   * @param node The Node instance
   * @return An array of value names
   */
  function getDisplayValueNames(&$node)
  {
    $displayValueStr = $node->getProperty('display_value');
    if (!strPos($displayValueStr, '|')) {
      $displayValues = array($displayValueStr);
    }
    else {
      $displayValues = split('\|', $displayValueStr);
    }
    return $displayValues;
  }
  /**
   * Get the display value for a Node defined by the 'display_value' property that may reference values of subnodes.
   * If the 'display_value' is an array ('|' separated strings) the pieces will be put together with ' - '.
   * If search for 'display_value' gives no result the function returns an empty string.
   * Example: 'name|Comment/text' shows the name of the Node together with the text of the first Comment child
   * @note If display_value is ambiguous because a parent has more than one children of a given type than the first child of
   *       that type will be chosen for display.
   * @param node A reference to the Node to display
   * @param useDisplayType True/False wether to use the display types that are associated with the values which the display value contains [default: false]
   * @param language The lanugage if values should be localized. Optional, default is Localization::getDefaultLanguage()
   * @param values An assoziative array holding key value pairs that the display node's values should match [maybe null].
   * @note The display type is configured via the display_type property of a value. It describes how the value should be displayed.
   *       The description is of the form @code type @endcode or @code type[attributes] @endcode
   *       - type: text|image
   *       - attributes: a string of attributes used in the HTML definition (e.g. 'height="50"')
   * @return The display string
   * @see DefaultValueRenderer::renderValue
   */
  function getDisplayValue(&$node, $useDisplayType=false, $language=null, $values=null)
  {
    return join(' - ', array_values(NodeUtil::getDisplayValues($node, $useDisplayType, $language, $values)));
  }
  /**
   * Does the same as DefaultValueRenderer::getDisplayValue() but returns the display value as associative array
   * @param node A reference to the Node to display
   * @param useDisplayType True/False wether to use the display types that are associated with the values which the display value contains [default: false]
   * @param language The lanugage if values should be localized. Optional, default is Localization::getDefaultLanguage()
   * @param values An assoziative array holding key value pairs that the display node's values should match [maybe null].
   * @return The display array
   */
  function getDisplayValues(&$node, $useDisplayType=false, $language=null, $values=null)
  {
    // localize node if requested
    $localization = &Localization::getInstance();
    if ($language != null) {
      $localization->loadTranslation($node, $language);
    }

    $displayArray = array();
    $persistenceFacade = &PersistenceFacade::getInstance();
    $formUtil = new FormUtil($language);
    $pathToShow = $node->getProperty('display_value');
    if (!strPos($pathToShow, '|')) {
      $pathToShowPieces = array($pathToShow);
    }
    else {
      $pathToShowPieces = split('\|', $pathToShow);
    }
    foreach($pathToShowPieces as $pathToShowPiece)
    {
      $tmpDisplay = '';
      $inputType = ''; // needed for the translation of a list value
      if ($pathToShowPiece != '')
      {
        $curNode = $node;
        $pieces = split('/', $pathToShowPiece);
        foreach ($pieces as $curPiece)
        {
          if (in_array($curPiece, $curNode->getValueNames()))
          {
            // we found a matching attribute/element
            $tmpDisplay = $curNode->getValue($curPiece);
            $properties = $curNode->getValueProperties($curPiece);
            $inputType = $properties['input_type'];
            $displayType = $properties['display_type'];
            break;
          }
          else
          {
            // see if a child type matches
            // see if the $value is valid for the child to look for
            if ($values != null)
            {
              if (PersistenceFacade::isKnownType($curPiece))
              {
                $template = &$persistenceFacade->create($curPiece, BUILDDEPTH_SINGLE);
                $possibleValues = $template->getValueNames();
                foreach ($values as $key => $value) {
                  if (!in_array($key, $possibleValues)) {
                    unset($values[$key]);
                  }
                }
                if (sizeOf($values) == 0) {
                  $values = null;
                }
              }
            }

            // the child is loaded -> take it
            $curNodeArray = $curNode->getChildrenEx(null, $curPiece, $values, null);
            if (sizeOf($curNodeArray) > 0) {
              $curNode = $curNodeArray[0];
            }
            // the child is not loaded -> try to load it
            else
            {
              $loaded = false;
              if (PersistenceFacade::isKnownType($curPiece))
              {
                $nodesOfTypePiece = $persistenceFacade->getOIDs($curPiece);
                foreach($curNode->getChildOIDs() as $childOID)
                {
                  if (in_array($childOID, $nodesOfTypePiece))
                  {
                    $curNode = &$persistenceFacade->load($childOID, BUILDDEPTH_SINGLE);
                    // localize node if requested
                    if ($language != null) {
                      $localization->loadTranslation($curNode, $language);
                    }
                    $matches = true;
                    if ($values != null)
                    {
                      // check values
                      foreach($values as $key => $value)
                      {
                        if ($curNode->getValue($key) != $value)
                        {
                          $matches = false;
                          break;
                        }
                      }
                    }
                    if ($matches)
                    {
                      $loaded = true;
                      break;
                    }
                  }
                }
              }
              if (!$loaded) {
                break;
              }
            }
          }
        }
      }
      $tmpDisplay = $formUtil->translateValue($tmpDisplay, $inputType);
      if (strlen($tmpDisplay) == 0) {
        $tmpDisplay = $node->getOID();
      }
      if ($useDisplayType)
      {
        // apply display type if desired
        if (!is_object($GLOBALS['gValueRenderer']))
        {
          $objectFactory = &ObjectFactory::getInstance();
          $GLOBALS['gValueRenderer'] = &$objectFactory->createInstanceFromConfig('implementation', 'ValueRenderer');
          if ($GLOBALS['gValueRenderer'] == null) {
            throw new ConfigurationException('ValueRenderer not defined in section implementation.');
          }
        }

        // get type and attributes from definition
        preg_match_all("/[\w][^\[\]]+/", $displayType, $matches);
        if (sizeOf($matches[0]) > 0) {
          list($type, $attributes) = $matches[0];
        }
        if (!$type || $type == '') {
          $type = 'text';
        }
        $tmpDisplay = $GLOBALS['gValueRenderer']->renderValue($type, $tmpDisplay, $attributes);
      }

      $displayArray[$pathToShowPiece] = $tmpDisplay;
    }
    return $displayArray;
  }
  /**
   * Get the display name for a Node type defined by the mappers 'alt' property.
   * @param type The name of the type
   * @return The display string
   */
  function getDisplayNameFromType($type)
  {
    $persistenceFacade = &PersistenceFacade::getInstance();
    $typeNode = $persistenceFacade->create($type, BUILDDEPTH_SINGLE);
    return $typeNode->getObjectDisplayName();
  }
  /**
   * Get a HTML input control for a given node value. The control is defined by the
   * 'input_type' property of the value. The property 'is_editable' is used to determine
   * wether the control should be enabled or not.
   * @param node A reference to the Node which contains the value
   * @param name The name of the value to construct the control for
   * @param dataType The type of the value [optional]
   *        (if type is omitted the first value of any type that matches will be used)
   * @param templateNode A Node which contains the value definition
   *        (if not given the definition will be taken from the node parameter) [optional]
   * @return The HTML control string (see FormUtil::getInputControl())
   */
  function getInputControl(&$node, $name, $dataType=null, $templateNode=null)
  {
    // set the datatype if not given (to the fist one found)
    if ($dataType == null)
    {
      $dataTypes = $node->getValueTypes($name);
      if (sizeof($dataTypes) > 0) {
        $dataType = $dataTypes[0];
      }
    }
    $controlName = NodeUtil::getInputControlName($node, $name, $dataType);
    if ($templateNode != null) {
      $properties = $templateNode->getValueProperties($name, $dataType);
    }
    else {
      $properties = $node->getValueProperties($name, $dataType);
    }
    $value = $node->getValue($name, $dataType);
    $formUtil = new FormUtil();
    return $formUtil->getInputControl($controlName, $properties['input_type'], $value, $properties['is_editable']);
  }
  /**
   * Get a HTML input control name for a given node value (see FormUtil::getInputControl()).
   * @param node A reference to the Node which contains the value
   * @param name The name of the value to construct the control for
   * @param dataType The type of the value [optional]
   *        (if type is omitted the first value of any type that matches will be used)
   * @return The HTML control name string in the form value-<datatype>-<name>-<oid>
   */
  function getInputControlName(&$node, $name, $dataType=null)
  {
    $fieldDelimiter = FormUtil::getInputFieldDelimiter();
    return 'value'.$fieldDelimiter.$dataType.$fieldDelimiter.$name.$fieldDelimiter.$node->getOID();
  }
  /**
   * Get the node value definition from a HTML input control name.
   * @param name The name of input control in the format defined by getInputControlName
   * @return An associative array with keys 'oid', 'name', 'dataType' or null if the name is not valid
   * If the dataType is empty, it defaults to DATATYPE_ATTRIBUTE
   */
  function getValueDefFromInputControlName($name)
  {
    if (!(strpos($name, 'value') === 0)) {
      return null;
    }
    $def = array();
    $fieldDelimiter = FormUtil::getInputFieldDelimiter();
    $pieces = split($fieldDelimiter, $name);
    if (!sizeof($pieces) == 4) {
      return null;
    }
    $forget = array_shift($pieces);
    $dataType = array_shift($pieces);
    if (strlen($dataType) > 0) {
      $def['dataType'] = intval($dataType);
    }
    else {
      $def['dataType'] = DATATYPE_ATTRIBUTE;
    }
    $def['name'] = array_shift($pieces);
    $def['oid'] = array_shift($pieces);

    return $def;
  }
  /**
   * Sort a list of Nodes and set the sort properties on Nodes of a given list. The two attributes (DATATPE_IGNORE)
   * 'hasSortUp', 'hasSortDown' (values (false,true)) will be added to each Node depending on
   * its list position. If applicable the attributes (DATATPE_IGNORE) 'prevoid'
   * and 'nextoid' resp. will be added to denote the neighboured Nodes.
   * The attributes will only be added if a Node has a sortkey value (DATATPE_IGNORE).
   * @param nodeList A reference to the list of Nodes
   */
  function setSortProperties(&$nodeList)
  {
    if(sizeof($nodeList) > 0 && $nodeList[0]->hasValue('sortkey', DATATYPE_IGNORE)) {
      $nodeList = Node::sort($nodeList, 'sortkey');
    }
    for ($i=0; $i<sizeof($nodeList); $i++)
    {
      if ($nodeList[$i]->hasValue('sortkey', DATATYPE_IGNORE))
      {
        $nodeList[$i]->setValue('hasSortUp', true, DATATYPE_IGNORE);
        $nodeList[$i]->setValue('hasSortDown', true, DATATYPE_IGNORE);

        if ($i == 0) {
          $nodeList[$i]->setValue('hasSortUp', false, DATATYPE_IGNORE);
        }
        else {
          $nodeList[$i]->setValue('prevoid', $nodeList[$i-1]->getOID(), DATATYPE_IGNORE);
        }
        if ($i == sizeof($nodeList)-1) {
          $nodeList[$i]->setValue('hasSortDown', false, DATATYPE_IGNORE);
        }
        else {
          $nodeList[$i]->setValue('nextoid', $nodeList[$i+1]->getOID(), DATATYPE_IGNORE);
        }
      }
    }
  }
  /**
   * Make all urls matching a given base url in a Node relative.
   * @param node A reference to the Node the holds the value
   * @param baseUrl The baseUrl to which matching urls will be made relative
   * @param recursive True/False wether to recurse into child Nodes or not (default: true)
   */
  function makeNodeUrlsRelative(&$node, $baseUrl, $recursive=true)
  {
    // use NodeProcessor to iterate over all Node values
    // and call the global convert function on each
    $processor = new NodeProcessor('makeValueUrlsRelative', array($baseUrl), new NodeUtil());
    $processor->run($node, $recursive);
  }
  /**
   * Make the urls matching a given base url in a Node value relative.
   * @param node A reference to the Node the holds the value
   * @param valueName The name of the value
   * @param dataType The dataType of the value
   * @param baseUrl The baseUrl to which matching urls will be made relative
   */
  function makeValueUrlsRelative(&$node, $valueName, $dataType, $baseUrl)
  {
    $value = $node->getValue($valueName, $dataType);

    // find urls in texts
    $urls = StringUtil::getUrls($value);
    // find direct attribute urls
    if (strpos($value, 'http://') === 0 || strpos($value, 'https://') === 0) {
      array_push($urls, $value);
    }
    // process urls
    foreach ($urls as $url)
    {
      // convert absolute urls matching baseUrl
      $urlConv = $url;
      if (strpos($url, $baseUrl) === 0) {
        $urlConv = str_replace($baseUrl, '', $url);
      }
      // replace url
      $value = str_replace($url, $urlConv, $value);
    }
    $node->setValue($valueName, $value, $dataType);
  }
  /**
   * Render all values in a list of Nodes using the DefaultValueRenderer.
   * @note Values will be translated before rendering using FormUtil::translateValue
   * @param nodes A reference to the array of Nodes
   * @param language The language code, if the translated values should be localized.
   *                 Optional, default is Localization::getDefaultLanguage()
   */
  function renderValues(&$nodes, $language=null)
  {
    // render the node values
    $nodeUtil = new NodeUtil();
    $formUtil = new FormUtil($language);
    $processor = new NodeProcessor('renderValue', array($formUtil), $nodeUtil);
    for($i=0; $i<sizeof($nodes); $i++)
    {
      // render values
      $processor->run($nodes[$i], false);
    }
  }
  /**
   * Callback to render a Node value
   * @see NodeProcessor
   * @note This method is used internally only
   */
  function renderValue(&$node, $valueName, $dataType, $formUtil)
  {
    if ($dataType == DATATYPE_ATTRIBUTE)
    {
      $value = $node->getValue($valueName, $dataType);
      // translate list values
      $value = $formUtil->translateValue($value, $node->getValueProperty($valueName, 'input_type', $dataType), true);
      // render the value to html
      $displayType = $node->getValueProperty($valueName, 'display_type', $dataType);
      if (strlen($displayType) == 0) {
        $displayType = 'text';
      }
      $renderer = new DefaultValueRenderer();
      $value = $renderer->renderValue($displayType, $value, $renderAttribs);
      // force set (the rendered value may not be satisfy validation rules)
      $node->setValue($valueName, $value, $dataType, true);
    }
  }
  /**
   * Translate all values in a list of Nodes using the DefaultValueRenderer.
   * @note Translation in this case refers to mapping list values from the key to the value
   * and should not be confused with localization, although values maybe localized using the
   * language parameter.
   * @param nodes A reference to the array of Nodes
   * @param language The language code, if the translated values should be localized.
   *                 Optional, default is Localization::getDefaultLanguage()
   */
  function translateValues(&$nodes, $language=null)
  {
    // translate the node values
    $nodeUtil = new NodeUtil();
    $formUtil = new FormUtil($language);
    $processor = new NodeProcessor('translateValue', array($formUtil), $nodeUtil);
    for($i=0; $i<sizeof($nodes); $i++)
    {
      // render values
      $processor->run($nodes[$i], false);
    }
  }
  /**
   * Callback to translate a Node value
   * @see NodeProcessor
   * @note This method is used internally only
   */
  function translateValue(&$node, $valueName, $dataType, $formUtil)
  {
    if ($dataType == DATATYPE_ATTRIBUTE)
    {
      $value = $node->getValue($valueName, $dataType);
      // translate list values
      $value = $formUtil->translateValue($value, $node->getValueProperty($valueName, 'input_type', $dataType), true);
      // force set (the rendered value may not be satisfy validation rules)
      $node->setValue($valueName, $value, $dataType, true);
    }
  }
  /**
   * Remove all values from a Node that are not a display value and don't have DATATYPE_IGNORE.
   * @param node The Node instance
   */
  function removeNonDisplayValues(&$node)
  {
    $displayValues = NodeUtil::getDisplayValueNames($node);
    $valueNames = $node->getValueNames();
    foreach($valueNames as $name) {
      if (!in_array($name, $displayValues)) {
        $node->removeValue($name);
      }
    }
  }
}
?>
