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
namespace wcmf\lib\model;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\Node;
use wcmf\lib\model\NodeValueIterator;
use wcmf\lib\model\ObjectQuery;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\PathDescription;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\presentation\control\ValueListProvider;
use wcmf\lib\util\StringUtil;

/**
 * NodeUtil provides services for the Node class. All methods are static.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NodeUtil {

  /**
   * Get the shortest paths that connect a type to another type.
   * @param $type The type to start from
   * @param $otherRole The role of the type at the other end (maybe null, if only type shoudl match)
   * @param $otherType The type at the other end (maybe null, if only role shoudl match)
   * @param $hierarchyType The hierarchy type that the other type has in relation to this type
   *                      'parent', 'child', 'undefined' or 'all' to get all relations (default: 'all')
   * @return An array of PathDescription instances
   */
  public static function getConnections($type, $otherRole, $otherType, $hierarchyType='all') {
    $paths = array();
    self::getConnectionsImpl($type, $otherRole, $otherType, $hierarchyType, $paths);
    $minLength = -1;
    $shortestPaths = array();
    foreach ($paths as $curPath) {
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
   * @param $type The type to start from
   * @param $otherRole The role of the type at the other end (maybe null, if only type shoudl match)
   * @param $otherType The type at the other end (maybe null, if only role shoudl match)
   * @param $hierarchyType The hierarchy type that the other type has in relation to this type
   *                      'parent', 'child', 'undefined' or 'all' to get all relations (default: 'all')
   * @param $result Array of PathDescriptions after execution
   * @param $currentPath Internal use only
   */
  protected static function getConnectionsImpl($type, $otherRole, $otherType,
          $hierarchyType, array &$result=array(), array $currentPath=array()) {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $mapper = $persistenceFacade->getMapper($type);

    // check relations
    $relationDescs = $mapper->getRelations($hierarchyType);
    foreach ($relationDescs as $relationDesc) {
      // loop detection
      $loopDetected = false;
      foreach ($currentPath as $pathPart) {
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
      $otherTypeFq = $otherType != null ? $persistenceFacade->getFullyQualifiedType($otherType) : null;
      if (($otherRole != null && $nextRole == $otherRole) || ($otherType != null && $nextType == $otherTypeFq)) {
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
   * Get the query condition used to select all related Nodes of a given role.
   * @param $node The Node to select the relatives for
   * @param $otherRole The role of the other nodes
   * @return The condition string to be used with StringQuery.
   */
  public static function getRelationQueryCondition($node, $otherRole) {
    $mapper = $node->getMapper();
    $relationDescription = $mapper->getRelation($otherRole);
    $otherType = $relationDescription->getOtherType();

    $query = new ObjectQuery($otherType, __CLASS__.__METHOD__.$node->getType().$otherRole);
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
   * Get the display value for a Node defined by the 'display_value' property.
   * If the 'display_value' is an array ('|' separated strings) the pieces will be put together with ' - '.
   * If search for 'display_value' gives no result the function returns an empty string.
   * Example: 'name|text' shows the name of the Node together with the content of the text attribute
   * @param $node Node instance to display
   * @param $language The language if values should be localized. Optional, default is Localization::getDefaultLanguage()
   * @note The display type is configured via the display_type property of a value. It describes how the value should be displayed.
   *       The description is of the form @code type @endcode or @code type[attributes] @endcode
   *       - type: text|image|link
   *       - attributes: a string of attributes used in the HTML definition (e.g. 'height="50"')
   * @return The display string
   */
  public static function getDisplayValue(Node $node, $language=null) {
    return join(' - ', array_values(self::getDisplayValues($node, $language)));
  }

  /**
   * Does the same as NodeUtil::getDisplayValue but returns the display value as associative array
   * @param $node Node instance to display
   * @param $language The language if values should be localized. Optional, default is Localization::getDefaultLanguage()
   * @return The display array
   */
  public static function getDisplayValues(Node $node, $language=null) {
    // localize node if requested
    $localization = ObjectFactory::getInstance('localization');
    if ($language != null) {
      $node = $localization->loadTranslation($node, $language);
    }

    $displayArray = array();
    $displayValueDef = $node->getProperty('display_value');
    if (strlen($displayValueDef) > 0) {
      $displayValuesNames = preg_split('/\|/', $displayValueDef);
      $mapper = $node->getMapper();
      foreach($displayValuesNames as $displayValueName) {
        $inputType = ''; // needed for the translation of a list value
        if ($displayValueName != '') {
          if ($mapper->hasAttribute($displayValueName)) {
            $attribute = $mapper->getAttribute($displayValueName);
            $inputType = $attribute->getInputType();
          }
          $tmpDisplay = $node->getValue($displayValueName);
        }

        // translate any list value
        $tmpDisplay = ValueListProvider::translateValue($tmpDisplay, $inputType, $language);
        if (strlen($tmpDisplay) == 0) {
          // fallback to oid
          $tmpDisplay = $node->getOID();
        }

        $displayArray[$displayValueName] = $tmpDisplay;
      }
    }
    return $displayArray;
  }

  /**
   * Make all urls matching a given base url in a Node relative.
   * @param $node Node instance that holds the value
   * @param $baseUrl The baseUrl to which matching urls will be made relative
   * @param $recursive Boolean whether to recurse into child Nodes or not (default: true)
   */
  public static function makeNodeUrlsRelative(Node $node, $baseUrl, $recursive=true) {
    // use NodeValueIterator to iterate over all Node values
    // and call the global convert function on each
    $iter = new NodeValueIterator($node, $recursive);
    for($iter->rewind(); $iter->valid(); $iter->next()) {
      self::makeValueUrlsRelative($iter->currentNode(), $iter->key(), $baseUrl);
    }
  }

  /**
   * Make the urls matching a given base url in a PersistentObject value relative.
   * @param $node Node instance that holds the value
   * @param $valueName The name of the value
   * @param $baseUrl The baseUrl to which matching urls will be made relative
   */
  private static function makeValueUrlsRelative(PersistentObject $object, $valueName, $baseUrl) {
    $value = $object->getValue($valueName);

    // find urls in texts
    $urls = StringUtil::getUrls($value);
    // find direct attribute urls
    if (strpos($value, 'http://') === 0 || strpos($value, 'https://') === 0) {
      $urls[] = $value;
    }
    // process urls
    foreach ($urls as $url) {
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
   * Translate all list values in a list of Nodes.
   * @note Translation in this case refers to mapping list values from the key to the value
   * and should not be confused with localization, although values maybe localized using the
   * language parameter.
   * @param $nodes A reference to the array of Node instances
   * @param $language The language code, if the translated values should be localized.
   *                 Optional, default is Localizat$objectgetDefaultLanguage()
   */
  public static function translateValues(&$nodes, $language=null) {
    // translate the node values
    for($i=0; $i<sizeof($nodes); $i++) {
      $iter = new NodeValueIterator($nodes[$i], false);
      for($iter->rewind(); $iter->valid(); $iter->next()) {
        self::translateValue($iter->currentNode(), $iter->key(), $language);
      }
    }
  }

  /**
   * Translate a PersistentObject list value.
   * @param $object The object whose value to translate
   * @param $valueName The name of the value to translate
   * @param $language The language to use
   */
  private static function translateValue(PersistentObject $object, $valueName, $language) {
    $value = $object->getValue($valueName);
    // translate list values
    $value = ValueListProvider::translateValue($value, $object->getValueProperty($valueName, 'input_type'), true, null, $language);
    // force set (the rendered value may not be satisfy validation rules)
    $object->setValue($valueName, $value, true);
  }

  /**
   * Remove all values from a Node that are not a display value.
   * @param $node The Node instance
   */
  public static function removeNonDisplayValues(Node $node) {
    $displayValueStr = $node->getProperty('display_value');
    $displayValues = preg_split('/\|/', $displayValueStr);
    $valueNames = $node->getValueNames();
    foreach($valueNames as $name) {
      if (!in_array($name, $displayValues)) {
        $node->removeValue($name);
      }
    }
  }

  /**
   * Remove all values from a Node that are not a primary key value.
   * @param $node The Node instance
   */
  public static function removeNonPkValues(Node $node) {
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
