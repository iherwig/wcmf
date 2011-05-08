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
require_once(WCMF_BASE."wcmf/lib/util/class.Log.php");
require_once(WCMF_BASE."wcmf/lib/util/class.StringUtil.php");
require_once(WCMF_BASE."wcmf/lib/model/class.Node.php");
require_once(WCMF_BASE."wcmf/lib/model/class.NodeIterator.php");
require_once(WCMF_BASE."wcmf/lib/model/class.NodeValueIterator.php");
require_once(WCMF_BASE."wcmf/lib/model/class.ObjectQuery.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.PathDescription.php");
require_once(WCMF_BASE."wcmf/lib/presentation/control/class.Control.php");
require_once(WCMF_BASE."wcmf/lib/presentation/renderer/class.ValueRenderer.php");

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
   * Get the shortest paths that connect a type to another type.
   * @param type The type to start from
   * @param otherRole The role of the type at the other end (maybe null, if only type shoudl match)
   * @param otherType The type at the other end (maybe null, if only role shoudl match)
   * @param hierarchyType The hierarchy type that the other type has in relation to this type
   *                      'parent', 'child', 'undefined' or 'all' to get all relations [default: 'all']
   * @return An array of PathDescription instances
   */
  public static function getConnections($type, $otherRole, $otherType, $hierarchyType='all')
  {
    $paths = array();
    self::getConnectionsImpl($type, $otherRole, $otherType, $hierarchyType, $paths);
    $minLength = -1;
    $shortestPaths = array();
    foreach ($paths as $curPath)
    {
      $curLength = $curPath->getPathLength();
      if ($minLength == -1 || $minLength > $curLength) {
        $minLength = $curLength;
        $shortestPaths = array($curPath);
      }
      elseif ($curLength == $minLength) {
        $shortestPaths[] = $curPath;
      }
    }
    return $shortestPaths;
  }

  /**
   * Get the relations that connect a type to another type.
   * @param type The type to start from
   * @param otherRole The role of the type at the other end (maybe null, if only type shoudl match)
   * @param otherType The type at the other end (maybe null, if only role shoudl match)
   * @param hierarchyType The hierarchy type that the other type has in relation to this type
   *                      'parent', 'child', 'undefined' or 'all' to get all relations [default: 'all']
   * @param result Array of PathDescriptions after execution
   * @param currentPath Internal use only
   */
  protected static function getConnectionsImpl($type, $otherRole, $otherType, 
          $hierarchyType, array &$result=array(), array $currentPath=array())
  {
    $persistenceFacade = PersistenceFacade::getInstance();
    $mapper = $persistenceFacade->getMapper($type);

    // check relations
    $relationDescs = $mapper->getRelations($hierarchyType);
    foreach ($relationDescs as $relationDesc)
    {
      // loop detection
      $loopDetected = false;
      foreach ($currentPath as $pathPart)
      {
        if ($relationDesc->isSameRelation($pathPart)) {
          $loopDetected = true;
          break;
        }
      }
      if ($loopDetected) {
        // continue with next relation
        continue;
      }

      $pathFound = null;
      $nextType = $relationDesc->getOtherType();
      $nextRole = $relationDesc->getOtherRole();
      if (($otherRole != null && $nextRole == $otherRole) || ($otherType != null && $nextType == $otherType)) {
        // other end found -> terminate
        $pathFound = $currentPath;
        $pathFound[] = $relationDesc;
      }
      else {
        // nothing found -> proceed with next generation
        $nextCurrentPath = $currentPath;
        $nextCurrentPath[] = $relationDesc;
        self::getConnectionsImpl($nextType, $otherRole, $otherType, $hierarchyType, $result, $nextCurrentPath);
      }

      // if a path is found, add it to the result
      if ($pathFound) {
        $result[] = new PathDescription($pathFound);
      }
    }
  }
  /**
   * Get the query condition used to select all Nodes of a type.
   * @param nodeType The Node type
   * @return The condition string to be used with StringQuery.
   */
  public static function getNodeQueryCondition($nodeType)
  {
    $query = new ObjectQuery($nodeType);
    return $query->getQueryCondition();
  }
  /**
   * Get the query condition used to select a special Node.
   * @param nodeType The Node type
   * @param oid The object id of the node
   * @return The condition string to be used with StringQuery.
   */
  public static function getSelfQueryCondition($nodeType, ObjectId $oid)
  {
    $query = new ObjectQuery($nodeType);
    $tpl = $query->getObjectTemplate($nodeType);
    $mapper = $tpl->getMapper();
    $ids = $oid->getId();
    $i = 0;
    foreach ($mapper->getPkNames() as $pkName) {
      $tpl->setValue($pkName, Criteria::asValue("=", $ids[$i++]));
    }
    return $query->getQueryString();
  }
  /**
   * Get the query condition used to select all related Nodes of a given role.
   * @param node The Node to select the relatives for
   * @param otherRole The role of the other nodes
   * @return The condition string to be used with StringQuery.
   */
  public static function getRelationQueryCondition($node, $otherRole)
  {
    $mapper = $node->getMapper();
    $relationDescription = $mapper->getRelation($otherRole);
    $otherType = $relationDescription->getOtherType();
    
    $query = new ObjectQuery($otherType);
    // add the primary keys of the node 
    // using the role name as alias (avoids ambiguous paths)
    $nodeTpl = $query->getObjectTemplate($node->getType(), $relationDescription->getThisRole());
    $oid = $node->getOID();
    $ids = $oid->getId();
    $i = 0;
    foreach ($mapper->getPkNames() as $pkName) {
      $nodeTpl->setValue($pkName, Criteria::asValue("=", $ids[$i++]));
    }
    // add the other type in the given relation
    $otherTpl = $query->getObjectTemplate($otherType);
    $nodeTpl->addNode($otherTpl, $otherRole);
    $condition = $query->getQueryCondition();
    // prevent selecting all objects, if the condition is empty
    if (strlen($condition) == 0) {
      $condition = 0;
    }
    return $condition;
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
   *       - type: text|image|link
   *       - attributes: a string of attributes used in the HTML definition (e.g. 'height="50"')
   * @return The display string
   * @see ValueRenderer::render
   */
  public static function getDisplayValue(Node $node, $useDisplayType=false, $language=null, $values=null)
  {
    return join(' - ', array_values(self::getDisplayValues($node, $useDisplayType, $language, $values)));
  }
  /**
   * Does the same as NodeUtil::getDisplayValue but returns the display value as associative array
   * @param node A reference to the Node to display
   * @param useDisplayType True/False wether to use the display types that are associated with the values which the display value contains [default: false]
   * @param language The lanugage if values should be localized. Optional, default is Localization::getDefaultLanguage()
   * @param values An assoziative array holding key value pairs that the display node's values should match [maybe null].
   * @return The display array
   */
  public static function getDisplayValues(Node $node, $useDisplayType=false, $language=null, $values=null)
  {
    // localize node if requested
    $localization = Localization::getInstance();
    if ($language != null) {
      $localization->loadTranslation($node, $language);
    }

    $displayArray = array();
    $persistenceFacade = PersistenceFacade::getInstance();
    $pathToShow = $node->getProperty('display_value');
    if (!strPos($pathToShow, '|')) {
      $pathToShowPieces = array($pathToShow);
    }
    else {
      $pathToShowPieces = preg_split('/\|/', $pathToShow);
    }
    foreach($pathToShowPieces as $pathToShowPiece)
    {
      $tmpDisplay = '';
      $inputType = ''; // needed for the translation of a list value
      if ($pathToShowPiece != '')
      {
        $curNode = $node;
        $pieces = preg_split('/\//', $pathToShowPiece);
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
              if ($persistenceFacade->isKnownType($curPiece))
              {
                $template = $persistenceFacade->create($curPiece, BUILDDEPTH_SINGLE);
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
              if ($persistenceFacade->isKnownType($curPiece))
              {
                $nodesOfTypePiece = $persistenceFacade->getOIDs($curPiece);
                foreach($curNode->getChildOIDs() as $childOID)
                {
                  if (in_array($childOID, $nodesOfTypePiece))
                  {
                    $curNode = $persistenceFacade->load($childOID, BUILDDEPTH_SINGLE);
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
      $tmpDisplay = Control::translateValue($tmpDisplay, $inputType, false, null, $language);
      if (strlen($tmpDisplay) == 0) {
        $tmpDisplay = $node->getOID();
      }
      if ($useDisplayType)
      {
        // get type and attributes from definition
        preg_match_all("/[\w][^\[\]]+/", $displayType, $matches);
        if (sizeOf($matches[0]) > 0) {
          list($displayType, $attributes) = $matches[0];
        }
        if (!$displayType || $displayType == '') {
          $displayType = 'text';
        }
        $tmpDisplay = ValueRenderer::render($displayType, $tmpDisplay, $attributes);
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
  public static function getDisplayNameFromType($type)
  {
    $persistenceFacade = PersistenceFacade::getInstance();
    $typeNode = $persistenceFacade->create($type, BUILDDEPTH_SINGLE);
    return $typeNode->getObjectDisplayName();
  }
  /**
   * Make all urls matching a given base url in a Node relative.
   * @param node A reference to the Node the holds the value
   * @param baseUrl The baseUrl to which matching urls will be made relative
   * @param recursive True/False wether to recurse into child Nodes or not (default: true)
   */
  public static function makeNodeUrlsRelative(Node $node, $baseUrl, $recursive=true)
  {
    // use NodeValueIterator to iterate over all Node values
    // and call the global convert function on each
    $iter = new NodeValueIterator($node, $recursive);
    while (!$iter->isEnd())
    {
      self::makeValueUrlsRelative($iter->getCurrentNode(), $iter->getCurrentAttribute(), $baseUrl);
      $iter->proceed();
    }
  }
  /**
   * Make the urls matching a given base url in a PersistentObject value relative.
   * @param node A reference to the Node the holds the value
   * @param valueName The name of the value
   * @param baseUrl The baseUrl to which matching urls will be made relative
   */
  private static function makeValueUrlsRelative(PersistentObject $object, $valueName, $baseUrl)
  {
    $value = $object->getValue($valueName);

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
    $object->setValue($valueName, $value);
  }
  /**
   * Render all values in a list of Nodes using the appropriate ValueRenderer.
   * @note Values will be translated before rendering using Control::translateValue
   * @param nodes A reference to the array of Nodes
   * @param language The language code, if the translated values should be localized.
   *                 Optional, default is Localization::getDefaultLanguage()
   */
  public static function renderValues(&$nodes, $language=null)
  {
    // render the node values
    for($i=0, $count=sizeof($nodes); $i<$count; $i++)
    {
      $iter = new NodeValueIterator($nodes[$i], false);
      while (!$iter->isEnd())
      {
        self::renderValue($iter->getCurrentNode(), $iter->getCurrentAttribute(), $language);
        $iter->proceed();
      }
    }
  }
  /**
   * Render a PersistentObject value
   * @param object The object whose value to render
   * @param valueName The name of the value to render
   * @param language The language to use
   */
  private static function renderValue(PersistentObject $object, $valueName, $language)
  {
    $value = $object->getValue($valueName);
    // translate list values
    $value = Control::translateValue($value, $object->getValueProperty($valueName, 'input_type'), true, null, $language);

    // render the value to html
    $displayType = $object->getValueProperty($valueName, 'display_type');
    if (strlen($displayType) == 0) {
      $displayType = 'text';
    }
    $value = ValueRenderer::render($displayType, $value, "");
    // force set (the rendered value may not be satisfy validation rules)
    $object->setValue($valueName, $value, true);
  }
  /**
   * Translate all list values in a list of Nodes.
   * @note Translation in this case refers to mapping list values from the key to the value
   * and should not be confused with localization, although values maybe localized using the
   * language parameter.
   * @param nodes A reference to the array of Nodes
   * @param language The language code, if the translated values should be localized.
   *                 Optional, default is Localizat$objectgetDefaultLanguage()
   */
  public static function translateValues(&$nodes, $language=null)
  {
    // translate the node values
    for($i=0; $i<sizeof($nodes); $i++)
    {
      $iter = new NodeValueIterator($nodes[$i], false);
      while (!$iter->isEnd())
      {
        self::translateValue($iter->getCurrentNode(), $iter->getCurrentAttribute(), $language);
        $iter->proceed();
      }
    }
  }
  /**
   * Translate a PersistentObject list value.
   * @param object The object whose value to translate
   * @param valueName The name of the value to translate
   * @param language The language to use
   */
  private static function translateValue(PersistentObject $object, $valueName, $language)
  {
    $value = $object->getValue($valueName);
    // translate list values
    $value = Control::translateValue($value, $object->getValueProperty($valueName, 'input_type'), true, null, $language);
    // force set (the rendered value may not be satisfy validation rules)
    $object->setValue($valueName, $value, true);
  }
  /**
   * Remove all values from a Node that are not a display value.
   * @param node The Node instance
   */
  public static function removeNonDisplayValues(Node $node)
  {
    $displayValues = $node->getDisplayValueNames($node);
    $valueNames = $node->getValueNames();
    foreach($valueNames as $name) {
      if (!in_array($name, $displayValues)) {
        $node->removeValue($name);
      }
    }
  }
  /**
   * Remove all values from a Node that are not a primary key value.
   * @param node The Node instance
   */
  public static function removeNonPkValues(Node $node)
  {
    $mapper = $node->getMapper();
    $pkValues = $mapper->getPkNames();
    $valueNames = $node->getValueNames();
    foreach($valueNames as $name) {
      if (!in_array($name, $pkValues)) {
        $node->removeValue($name);
      }
    }
  }
}
?>
