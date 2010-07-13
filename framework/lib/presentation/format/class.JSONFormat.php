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
require_once(BASE."wcmf/lib/util/class.JSONUtil.php");
require_once(BASE."wcmf/lib/model/class.NodeSerializer.php");
require_once(BASE."wcmf/lib/presentation/format/class.HierarchicalFormat.php");

/**
 * JSONFormatter collects the response data from all executed controllers
 * into one response array and returns it all at once at the end of 
 * script execution. This prevents from having multiple junks of json 
 * from each controller response that can't be decoded by clients.
 */
$GLOBALS['gJSONData'] = array();
$GLOBALS['gJSONUsed'] = false;
function gPrintJSONResult()
{
  if ($GLOBALS['gJSONUsed'])
  {
    $data = $GLOBALS['gJSONData'];
    if ($data != null)
    {
      $encoded = JSONUtil::encode($data);
      print($encoded);
    }
  }
}
register_shutdown_function('gPrintJSONResult');

/**
 * @class JSONFormat
 * @ingroup Format
 * @brief JSONFormat realizes the JSON request/response format. All data will
 * be de-/serialized using the json_encode/json_encode method if avalaible or
 * JSON.php as fallback, except for Nodes. Nodes are serialized into an array
 * before encoding (see JSONFormat::serializeValue). On serialization the data
 * will be outputted directly using the print command.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class JSONFormat extends HierarchicalFormat
{
  /**
   * @see HierarchicalFormat::beforeDeserialize()
   */
  function beforeDeserialize(&$data)
  {
    // decode the json data into an array
    foreach(array_keys($data) as $key) {
      $data[$key] = &JSONUtil::decode($data[$key], true);
    }
  }

  /**
   * @see HierarchicalFormat::afterSerialize()
   */
  function afterSerialize(&$data)
  {
    // merge data into global data array
    // new values override old
    $GLOBALS['gJSONData'] = array_merge($GLOBALS['gJSONData'], $data);
    $GLOBALS['gJSONUsed'] = true;
  }

  /**
   * @see HierarchicalFormat::isSerializedNode()
   */
  function isSerializedNode($key, &$value)
  {
    return ((is_object($value) || is_array($value)) && isset($value['oid']) && isset($value['type']));
  }

  /**
   * @see HierarchicalFormat::serializeNode()
   */
  function serializeNode($key, &$value)
  {
    // use NodeSerializer to serialize
    return NodeSerializer::serializeNode($value, false);
  }

  /**
   * @see HierarchicalFormat::deserializeNode()
   */
  function &deserializeNode($key, &$value)
  {
    if (is_array($value)) {
      $type = $value['type'];
    }
    if (is_object($value)) {
      $type = $value->type;
    }
    // use NodeSerializer to deserialize
    $node = &NodeSerializer::deserializeNode($type, $value, false);
    return $node;
  }
}
?>
