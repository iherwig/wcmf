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
   * @param string $type The type to start from
   * @param string $otherRole The role of the type at the other end (maybe null, if only type shoudl match)
   * @param string $otherType The type at the other end (maybe null, if only role shoudl match)
   * @param string $hierarchyType The hierarchy type that the other type has in relation to this type
   *                      'parent', 'child', 'undefined' or 'all' to get all relations (default: 'all')
   * @return array<PathDescription>
   */
  public static function getConnections(string $type, string $otherRole, string $otherType, string $hierarchyType='all'): array {
    $paths = [];
    self::getConnectionsImpl($type, $otherRole, $otherType, $hierarchyType, $paths);
    $minLength = -1;
    $shortestPaths = [];
    foreach ($paths as $curPath) {
      $curLength = $curPath->getPathLength();
      if ($minLength == -1 || $minLength > $curLength) {
        $minLength = $curLength;
        $shortestPaths = [$curPath];
      }
      elseif ($curLength == $minLength) {
        $shortestPaths[] = $curPath;
      }
    }
    return $shortestPaths;
  }

  /**
   * Get the relations that connect a type to another type.
   * @param string $type The type to start from
   * @param string $otherRole The role of the type at the other end (maybe null, if only type shoudl match)
   * @param string $otherType The type at the other end (maybe null, if only role shoudl match)
   * @param string $hierarchyType The hierarchy type that the other type has in relation to this type
   *                      'parent', 'child', 'undefined' or 'all' to get all relations (default: 'all')
   * @param array $result Array of PathDescriptions after execution
   * @param array $currentPath Internal use only
   */
  protected static function getConnectionsImpl(string $type, string $otherRole, string $otherType,
      string $hierarchyType, array &$result=[], array $currentPath=[]) {
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
   * @param Node $node The Node to select the relatives for
   * @param string $otherRole The role of the other nodes
   * @return string condition string to be used with StringQuery.
   */
  public static function getRelationQueryCondition(Node $node, string $otherRole): string {
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
   * Get the display value for a PersistentObject defined by the 'displayValues' property.
   * If the 'displayValues' property is an array the items will be put together with ' - '.
   * If the 'displayValues' property is empty the function returns an empty string.
   * Example: 'name,text' shows the name of the Node together with the content of the text attribute
   * @param PersistentObject $object PersistentObject instance to display
   * @param string $language The language if values should be localized. Optional, default is Localization::getDefaultLanguage()
   * @return string
   */
  public static function getDisplayValue(PersistentObject $object, string $language=null): string {
    return join(' - ', array_values(self::getDisplayValues($object, $language)));
  }

  /**
   * Does the same as NodeUtil::getDisplayValue but returns the display values as associative array
   * @param PersistentObject $object PersistentObject instance to display
   * @param string $language The language if values should be localized. Optional, default is Localization::getDefaultLanguage()
   * @return array<string>
   */
  public static function getDisplayValues(PersistentObject $object, string $language=null): array {
    // localize node if requested
    $localization = ObjectFactory::getInstance('localization');
    if ($language != null) {
      $object = $localization->loadTranslation($object, $language);
    }

    $displayArray = [];
    $displayValuesNames = $object->getProperty('displayValues');
    if (sizeof($displayValuesNames) > 0) {
      $mapper = $object->getMapper();
      foreach($displayValuesNames as $displayValueName) {
        $inputType = ''; // needed for the translation of a list value
        if ($displayValueName != '') {
          if ($mapper->hasAttribute($displayValueName)) {
            $attribute = $mapper->getAttribute($displayValueName);
            $inputType = $attribute->getInputType();
          }
          $tmpDisplay = $object->getValue($displayValueName);
        }

        // translate any list value
        $tmpDisplay = ValueListProvider::translateValue($tmpDisplay, $inputType, $language);
        if (strlen($tmpDisplay) == 0) {
          // fallback to oid
          $tmpDisplay = $object->getOID();
        }

        $displayArray[$displayValueName] = $tmpDisplay;
      }
    }
    return $displayArray;
  }

  /**
   * Make all urls matching a given base url in a Node relative.
   * @param PersistentObject $object PersistentObject instance that holds the value
   * @param string $baseUrl The baseUrl to which matching urls will be made relative
   * @param bool $recursive Boolean whether to recurse into child Nodes or not (default: true)
   */
  public static function makeNodeUrlsRelative(PersistentObject $object, string $baseUrl, bool $recursive=true) {
    // use NodeValueIterator to iterate over all Node values
    // and call the global convert function on each
    $iter = new NodeValueIterator($object, $recursive);
    for($iter->rewind(); $iter->valid(); $iter->next()) {
      self::makeValueUrlsRelative($iter->currentNode(), $iter->key(), $baseUrl);
    }
  }

  /**
   * Make the urls matching a given base url in a PersistentObject value relative.
   * @param PersistentObject $object PersistentObject instance that holds the value
   * @param string $valueName The name of the value
   * @param string $baseUrl The baseUrl to which matching urls will be made relative
   */
  private static function makeValueUrlsRelative(PersistentObject $object, string $valueName, string $baseUrl) {
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
   * Translate all list values in a list of PersistentObject instances.
   * @note Translation in this case refers to mapping list values from the key to the value
   * and should not be confused with localization, although values maybe localized using the
   * language parameter.
   * @param array $objects A reference to the array of PersistentObject instances
   * @param string $language The language code, if the translated values should be localized.
   *                 Optional, default is Localizat$objectgetDefaultLanguage()
   * @param string $itemDelim Delimiter string for array values (optional, default: ", ")
   */
  public static function translateValues(array &$objects, string $language=null, string $itemDelim=", ") {
    // translate the node values
    for($i=0; $i<sizeof($objects); $i++) {
      $iter = new NodeValueIterator($objects[$i], false);
      for($iter->rewind(); $iter->valid(); $iter->next()) {
        self::translateValue($iter->currentNode(), $iter->key(), $language, $itemDelim);
      }
    }
  }

  /**
   * Translate a PersistentObject list value.
   * @param PersistentObject $object The object whose value to translate
   * @param string $valueName The name of the value to translate
   * @param string $language The language to use
   * @param string $itemDelim Delimiter string for array values (optional, default: ", ")
   */
  public static function translateValue(PersistentObject $object, string $valueName, string $language, string $itemDelim=", ") {
    $value = $object->getValue($valueName);
    // translate list values
    $value = ValueListProvider::translateValue($value, $object->getValueProperty($valueName, 'input_type'), $language, $itemDelim);
    // force set (the rendered value may not be satisfy validation rules)
    $object->setValue($valueName, $value, true);
  }

  /**
   * Remove all values from a PersistentObject that are not a display value.
   * @param PersistentObject $node The PersistentObject instance
   */
  public static function removeNonDisplayValues(PersistentObject $object) {
    $displayValues = $object->getProperty('displayValues');
    $valueNames = $object->getValueNames();
    foreach($valueNames as $name) {
      if (!in_array($name, $displayValues)) {
        $object->removeValue($name);
      }
    }
  }

  /**
   * Remove all values from a PersistentObject that are not a primary key value.
   * @param PersistentObject $object The PersistentObject instance
   */
  public static function removeNonPkValues(PersistentObject $object) {
    $mapper = $object->getMapper();
    $pkValues = $mapper->getPkNames();
    $valueNames = $object->getValueNames();
    foreach($valueNames as $name) {
      if (!in_array($name, $pkValues)) {
        $object->removeValue($name);
      }
    }
  }
}
?>
