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
require_once(WCMF_BASE."wcmf/lib/model/NodeSerializer.php");
require_once(WCMF_BASE."wcmf/lib/presentation/format/Formatter.php");
require_once(WCMF_BASE."wcmf/lib/presentation/format/HierarchicalFormat.php");

/**
 * Define the message format
 */
define("MSG_FORMAT_SOAP", "SOAP");

/**
 * @class SOAPFormat
 * @ingroup Format
 * @brief SOAPFormat realizes the SOAP request/response format. Nodes are serialized
 * into an array (the nusoap library creates the XML)
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SOAPFormat extends HierarchicalFormat
{
  /**
   * @see HierarchicalFormat::isSerializedNode()
   */
  protected function isSerializedNode($value)
  {
    return ObjectID::isValid($value);
  }

  /**
   * @see HierarchicalFormat::serializeNode()
   */
  protected function serializeNode($value)
  {
    // use NodeSerializer to serialize
    return NodeSerializer::serializeNode($value);
  }
  /**
   * @see HierarchicalFormat::deserializeNode()
   */
  protected function deserializeNode($value)
  {
    $oidStr = $key;
    $oid = ObjectId::parse($oidStr);
    if ($oid == null) {
      throw new IllegalArgumentException("The object id '".$oid."' is invalid");
    }

    // use NodeSerializer to deserialize
    $node = NodeSerializer::deserializeNode($value);
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug($node->toString(), __CLASS__);
    }
    
    return $node;
  }
}

// register this format
Formatter::registerFormat(MSG_FORMAT_SOAP, "SOAPFormat");
?>
