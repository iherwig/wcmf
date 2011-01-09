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
require_once(WCMF_BASE."wcmf/lib/util/class.JSONUtil.php");
require_once(WCMF_BASE."wcmf/lib/model/class.NodeSerializer.php");
require_once(WCMF_BASE."wcmf/lib/presentation/format/class.HierarchicalFormat.php");

/**
 * Define the message format
 */
define("MSG_FORMAT_JSON", "JSON");

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
      if (Log::isDebugEnabled('JSONFormat'))
      {
        Log::debug($data, 'JSONFormat');
        Log::debug($encoded, 'JSONFormat');
      }
      header("Content-Type: application/json");
      print($encoded);
    }
  }
}
register_shutdown_function('gPrintJSONResult');

/**
 * @class JSONFormat
 * @ingroup Format
 * @brief JSONFormat realizes the JSON request/response format. All data will
 * be de-/serialized using the json_encode/json_encode method except for Nodes.
 * Nodes are serialized into an array before encoding (see JSONFormat::serializeValue). 
 * On serialization the data will be outputted directly using the print command.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class JSONFormat extends HierarchicalFormat
{
  /**
   * @see HierarchicalFormat::beforeDeserialize()
   */
  protected function beforeDeserialize(&$data)
  {
    // decode the json data into an array
    foreach(array_keys($data) as $key) {
      $data[$key] = &JSONUtil::decode($data[$key], true);
    }
  }

  /**
   * @see HierarchicalFormat::afterSerialize()
   */
  protected function afterSerialize(&$data)
  {
    // merge data into global data array
    // new values override old
    $GLOBALS['gJSONData'] = array_merge($GLOBALS['gJSONData'], $data);
    $GLOBALS['gJSONUsed'] = true;
  }

  /**
   * @see HierarchicalFormat::isSerializedNode()
   */
  protected function isSerializedNode($key, &$value)
  {
    $syntaxOk = ((is_object($value) || is_array($value)) && 
      isset($value['className']) && isset($value['oid']) && isset($value['attributes']));
    // check for oid variables
    if ($syntaxOk && preg_match('/^\{.+\}$/', $value['oid'])) {
      $syntaxOk = false;
    }
    return $syntaxOk;
  }

  /**
   * @see HierarchicalFormat::serializeNode()
   */
  protected function serializeNode($key, &$value)
  {
    // use NodeSerializer to serialize
    return NodeSerializer::serializeNode($value);
  }

  /**
   * @see HierarchicalFormat::deserializeNode()
   */
  protected function deserializeNode($key, &$value)
  {
    if (is_array($value)) {
      $oidStr = $value['oid'];
    }
    if (is_object($value)) {
      $oidStr = $value->oid;
    }
    $oid = ObjectId::parse($oidStr);
    if ($oid == null) {
      throw new IllegalArgumentException("The object id '".$oid."' is invalid");
    }

    // use NodeSerializer to deserialize
    $node = NodeSerializer::deserializeNode($type, $value, false);
    $node->setOID($oid);
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug($node->toString(), __CLASS__);
    }
    return $node;
  }
}

// register this format
Formatter::registerFormat(MSG_FORMAT_JSON, "JSONFormat");
?>
