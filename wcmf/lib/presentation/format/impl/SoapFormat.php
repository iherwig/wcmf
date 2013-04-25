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
namespace wcmf\lib\presentation\format\impl;

use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\core\Log;
use wcmf\lib\model\NodeSerializer;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\presentation\format\impl\HierarchicalFormat;

/**
 * SoapFormat realizes the SOAP request/response format. Nodes are serialized
 * into an array (the nusoap library creates the XML)
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SoapFormat extends HierarchicalFormat {

  protected $_serializer = null;

  /**
   * @see Format::getMimeType()
   */
  public function getMimeType() {
    return 'application/soap+xml';
  }

  /**
   * Set the NodeSerializer instance to use
   * @param serializer NodeSerializer
   */
  public function setSerializer(NodeSerializer $serializer) {
    $this->_serializer = $serializer;
  }

  /**
   * @see HierarchicalFormat::isSerializedNode()
   */
  protected function isSerializedNode($value) {
    return ObjectID::isValid($value);
  }

  /**
   * @see HierarchicalFormat::serializeNode()
   */
  protected function serializeNode($value) {
    if ($this->_serializer == null) {
      throw new ConfigurationException("The serializer is not set.");
    }
    $node = $this->_serializer->serializeNode($value);
  }

  /**
   * @see HierarchicalFormat::deserializeNode()
   */
  protected function deserializeNode($value) {
    if ($this->_serializer == null) {
      throw new ConfigurationException("The serializer is not set.");
    }
    $oidStr = $key;
    $oid = ObjectId::parse($oidStr);
    if ($oid == null) {
      throw new IllegalArgumentException("The object id '".$oid."' is invalid");
    }

    // use NodeSerializer to deserialize
    $node = $this->_serializer->deserializeNode($value);
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug($node->toString(), __CLASS__);
    }

    return $node;
  }
}
?>
