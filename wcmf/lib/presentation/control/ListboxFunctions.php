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
require_once("base_dir.php");

require_once(WCMF_BASE."wcmf/lib/util/FileUtil.php");
require_once(WCMF_BASE."wcmf/lib/presentation/WCMFInifileParser.php");
require_once(WCMF_BASE."wcmf/lib/persistence/PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/i18n/Localization.php");

/**
 * Global function for id (oid) retrieval. For parameters see PersistenceFacade::getOIDs().
 * If the type is a Node the display value for an oid is defined by the 'display_value' property of the corresponding type.
 * If not given the oid is displayed.
 * @param type The type to retrieve the ids (oids) for
 * @param queryStr A query to be used with StringQuery [default: null]
 * @param orderbyStr String with comma separated list of attributes to use for sorting e.g. 'Author.name ASC, Author.created DESC' [default: null]
 * @param realOIDs True/False indicates wether to fetch ids (false) or oids (true) [default: false]
 * @param language A language code if the returned data should be localized, optional [default: null]
 * @return An assoziative array with the database ids (or object ids depending on the last parameter) as keys and the display values as values
 * @note This function is especially useful as fkt parameter for list input type see Control::render()
 */
function g_getOIDs($type, $queryStr=null, $orderbyStr=null, $realOIDs=false, $language=null)
{
  if (!PersistenceFacade::isKnownType($type)) {
    throw new IllegalArgumentException("Illegal type given: ".$type);
  }
  $persistenceFacade = PersistenceFacade::getInstance();
  $localization = Localization::getInstance();

  // see if the type has a display value defined (via the 'display_value' property)
  $hasDisplayValue = false;
  $template = &$persistenceFacade->create($type, BUILDDEPTH_SINGLE);
  if ($template instanceof Node && $template->getProperty('display_value') != '') {
    $hasDisplayValue = true;
  }
  // create the array
  $result = array();

  // create the null entry
  if ($realOIDs) {
    $result[PersistenceFacade::composeOID(array('type' => $type, 'id' => array('')))] = "";
  }
  else {
    $result[""] = "";
  }
  // create the real entries
  $orderby = null;
  if ($orderbyStr != null) {
    $orderby = preg_split('/,/', $orderbyStr);
  }
  $query = new StringQuery($type);
  $query->setConditionString($queryStr);
  $pagingInfo = new PagingInfo();
  $nodes = $query->execute(BUILDDEPTH_SINGLE, $orderby, $pagingInfo);
  for($i=0; $i<sizeof($nodes); $i++)
  {
    $oid = $nodes[$i]->getOID();
    if ($realOIDs) {
      $key = $oid;
    }
    else {
      $key = join(':', $oid->getId());
    }

    // translate the node if requested
    if ($language != null) {
      $localization->loadTranslation($nodes[$i], $language, true, true);
    }

    $displayValue = $oid;
    if ($hasDisplayValue) {
      $displayValue = $nodes[$i]->getDisplayValue();
    }
    $result[$key] = $displayValue;
  }
  return $result;
}

/**
 * Global function that retrieves all oids of a given type that may be assoziated
 * with a given parent (except for those that are already assoziated).
 * This method is used to fill listboxes in the assoziated view.
 * @return An associative array of object ids (key: oid, value: display value)
 */
function g_getObjects($type, $parentOID)
{
  $persistenceFacade = &PersistenceFacade::getInstance();
  $result = array();
  if (!PersistenceFacade::isValidOID($parentOID)) {
    throw new IllegalArgumentException("Illegal parent oid given: ".$parentOID);
  }
  if (!PersistenceFacade::isKnownType($type)) {
    throw new IllegalArgumentException("Illegal type given: ".$type, __FILE__, __LINE__);
  }
  // collect children from parent
  $parent = $persistenceFacade->load($parentOID, 1);
  $children = $parent->getChildrenEx(null, $type, null, null);
  $childOIDs = array();
  foreach($children as $child) {
    $childOIDs[sizeof($childOIDs)] = $child->getOID();
  }
  // collect all possible objects
  $query = new ObjectQuery($type);
  $nodes = $query->execute(BUILDDEPTH_SINGLE);
  $oids = $persistenceFacade->getOIDs($type);
  for($i=0; $i<sizeof($nodes); $i++)
  {
    $node = &$nodes[$i];
    $oid = $node->getOID();
    if (!in_array($oid, $childOIDs)) {
      $result[$oid] = $node->getObjectDisplayName().": ".$node->getDisplayValue();
    }
  }
  return $result;
}

/**
 * Global function that retrieves all entity types.
 * This method is used to fill listboxes in the assoziated view.
 * @return An associative array of type names (key: type, value: dispaly name)
 */
function g_getTypes()
{
  $persistenceFacade = PersistenceFacade::getInstance();
  $types = PersistenceFacade::getKnownTypes();
  foreach($types as $type)
  {
    $node = $persistenceFacade->create($type, BUILDDEPTH_SINGLE);
    $result[$type] = $node->getObjectDisplayName();
  }
  asort($result);
  return $result;
}

/**
 * Global function that makes an associate array from oids given as '|' separated string.
 * This method is used to fill listboxes in the assoziated view.
 * @return An associative array of object ids (key: oid, value: display value)
 */
$g_oidArray = array();
function g_getOIDArray($oidStringList)
{
  global $g_oidArray;
  if (!isset($g_oidArray[$oidStringList]))
  {
    $persistenceFacade = PersistenceFacade::getInstance();
    $result = array();
    if (strpos($oidStringList, '|') === false) {
      $oids = array($oidStringList);
    }
    else {
      $oids = preg_split('/\|/', $oidStringList);
    }
    foreach($oids as $oid)
    {
      if (PersistenceFacade::isValidOID($oid))
      {
        $node = &$persistenceFacade->load($oid, BUILDDEPTH_SINGLE);
        $result[$node->getOID()] = DisplayController::getDisplayText($node);
      }
      else {
        $result[$oid] = $oid;
      }
    }
    $g_oidArray[$oidStringList] = $result;
  }
  return $g_oidArray[$oidStringList];
}

/**
 * Global function that retrieves all config files.
 * This method is used to fill listboxes in the assoziated view.
 * @return An associative array of config file names (key: filename, value: filename)
 */
function g_getConfigFiles()
{
  $result = array();
  $configFiles = WCMFInifileParser::getIniFiles();
  foreach ($configFiles as $file)
  {
    $file = basename($file);
    $result[$file] = $file;
  }
  $result[''] = '';
  return $result;
}

/**
 * Global function for backup name retrieval.
 * @return An array of backup names
 */
function g_getBackupNames()
{
  $result = array();

  $parser = &InifileParser::getInstance();
  if (($backupDir = $parser->getValue('backupDir', 'cms')) === false) {
    throw new ConfigurationException($parser->getErrorMsg());
  }
  $fileUtil = new FileUtil();
  $folders = $fileUtil->getFiles($backupDir, '/./');
  foreach($folders as $folder) {
    $result[$folder] = $folder;
  }
  $result[""] = "";
  return $result;
}
?>
