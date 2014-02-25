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

use wcmf\lib\model\NodeSerializer;
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
    if ($this->_serializer == null) {
      throw new ConfigurationException("The serializer is not set.");
    }
    return $this->_serializer->isSerializedNode($value);
  }

  /**
   * @see HierarchicalFormat::serializeNode()
   */
  protected function serializeNode($value) {
    if ($this->_serializer == null) {
      throw new ConfigurationException("The serializer is not set.");
    }
    $node = $this->_serializer->serializeNode($value);
    return $node;
  }

  /**
   * @see HierarchicalFormat::deserializeNode()
   */
  protected function deserializeNode($value) {
    if ($this->_serializer == null) {
      throw new ConfigurationException("The serializer is not set.");
    }
    $result = $this->_serializer->deserializeNode($value);
    return $result;
  }
}
?>
